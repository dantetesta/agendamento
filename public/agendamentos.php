<?php
/**
 * Página de Agendamentos - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Agendamento.php';
require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Models/Disponibilidade.php';
require_once __DIR__ . '/../app/Models/Configuracao.php';
require_once __DIR__ . '/../app/Models/Tag.php';

Auth::requireAuth();

$user = Auth::user();
$agendamentoModel = new Agendamento();
$clienteModel = new Cliente();
$tagModel = new Tag();
$disponibilidadeModel = new Disponibilidade();
$configuracaoModel = new Configuracao();

// Busca configurações do professor (duração e intervalo)
$config = $configuracaoModel->getByProfessor(Auth::id());
$duracaoAula = $config['duracao_aula'] ?? 60;
$intervaloAula = $config['intervalo'] ?? 15;

// Busca disponibilidades do professor
$disponibilidades = $disponibilidadeModel->getByProfessor(Auth::id());

// Agrupa disponibilidades por dia da semana
$disponibilidadesPorDia = [];
foreach ($disponibilidades as $disp) {
    $disponibilidadesPorDia[$disp['dia_semana']][] = [
        'inicio' => substr($disp['hora_inicio'], 0, 5),
        'fim' => substr($disp['hora_fim'], 0, 5)
    ];
}

// Busca tags de SERVIÇO (para classificar tipos de atendimento)
$tagsServico = $tagModel->getByCategoria('servico');

// Busca todos os agendamentos futuros para validação de conflitos
$agendamentosExistentes = $agendamentoModel->getByProfessor(Auth::id());
$agendamentosPorData = [];
foreach ($agendamentosExistentes as $ag) {
    if ($ag['data'] >= date('Y-m-d')) {
        $agendamentosPorData[$ag['data']][] = [
            'id' => $ag['id'],
            'inicio' => substr($ag['hora_inicio'], 0, 5),
            'fim' => substr($ag['hora_fim'], 0, 5),
            'aluno' => $ag['aluno']
        ];
    }
}

$errors = [];
$editando = null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Criar/Editar agendamento
    if (isset($_POST['salvar'])) {
        $id = $_POST['id'] ?? null;
        $aluno = sanitize($_POST['aluno'] ?? '');
        $data = $_POST['data'] ?? '';
        $horaInicio = $_POST['hora_inicio'] ?? '';
        $horaFim = $_POST['hora_fim'] ?? '';
        $descricao = sanitize($_POST['descricao'] ?? '');
        $tagServicoId = !empty($_POST['tag_servico_id']) ? (int)$_POST['tag_servico_id'] : null;
        
        // Validação
        if (empty($aluno)) {
            $errors[] = 'O nome do aluno é obrigatório.';
        }
        
        if (empty($data)) {
            $errors[] = 'A data é obrigatória.';
        }
        
        if (empty($horaInicio) || empty($horaFim)) {
            $errors[] = 'Horário de início e fim são obrigatórios.';
        }
        
        if (strtotime($horaFim) <= strtotime($horaInicio)) {
            $errors[] = 'Horário de fim deve ser maior que o de início.';
        }
        
        // Verifica disponibilidade
        if (empty($errors)) {
            if (!$agendamentoModel->isHorarioDisponivel(Auth::id(), $data, $horaInicio, $horaFim, $id)) {
                $errors[] = 'Este horário já está ocupado.';
            }
        }
        
        if (empty($errors)) {
            $dados = [
                'professor_id' => Auth::id(),
                'aluno' => $aluno,
                'data' => $data,
                'hora_inicio' => $horaInicio,
                'hora_fim' => $horaFim,
                'descricao' => $descricao,
                'tag_servico_id' => $tagServicoId
            ];
            
            if ($id) {
                // EDITAR
                $agendamentoModel->update($id, $dados);
                setFlash('success', 'Agendamento atualizado com sucesso!');
            } else {
                // CRIAR - Verifica se é recorrente
                $repetir = isset($_POST['repetir_agendamento']) && $_POST['repetir_agendamento'];
                
                if ($repetir) {
                    // VERSÃO SIMPLES: Repetir a cada X dias, Y vezes
                    $repetirDias = (int)($_POST['repetir_dias'] ?? 7);
                    $repetirVezes = (int)($_POST['repetir_vezes'] ?? 10);
                    
                    $criados = 0;
                    $dataAtual = new DateTime($data);
                    
                    for ($i = 0; $i < $repetirVezes; $i++) {
                        $dadosRecorrente = $dados;
                        $dadosRecorrente['data'] = $dataAtual->format('Y-m-d');
                        
                        // Verifica se horário está disponível
                        if ($agendamentoModel->isHorarioDisponivel(Auth::id(), $dadosRecorrente['data'], $horaInicio, $horaFim)) {
                            $agendamentoModel->create($dadosRecorrente);
                            $criados++;
                        }
                        
                        // Adiciona X dias para próxima data
                        $dataAtual->modify("+{$repetirDias} days");
                    }
                    
                    setFlash('success', "✅ {$criados} agendamentos criados com sucesso!");
                } else {
                    // Agendamento único
                    $agendamentoModel->create($dados);
                    setFlash('success', 'Agendamento criado com sucesso!');
                }
            }
            
            redirect('/agendamentos');
        }
    }
    
    // Deletar agendamento
    if (isset($_POST['deletar'])) {
        $id = $_POST['id'] ?? null;
        
        if ($id) {
            $agendamento = $agendamentoModel->findById($id);
            
            if ($agendamento && $agendamento['professor_id'] == Auth::id()) {
                $agendamentoModel->delete($id);
                setFlash('success', 'Agendamento excluído com sucesso!');
            } else {
                setFlash('error', 'Agendamento não encontrado.');
            }
        }
        
        redirect('/agendamentos');
    }
}

// Editar agendamento
if (isset($_GET['editar'])) {
    $editando = $agendamentoModel->findById($_GET['editar']);
    
    if (!$editando || $editando['professor_id'] != Auth::id()) {
        setFlash('error', 'Agendamento não encontrado.');
        redirect('/agendamentos');
    }
}

// Busca todos agendamentos
$agendamentos = $agendamentoModel->getByProfessor(Auth::id());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos - Agenda do Professor Inteligente</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Agendamento Recorrente -->
    <link rel="stylesheet" href="/assets/css/agendamento-recorrente.css?v=<?= time() ?>">
    
    <style>
        .sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) {
            .sidebar.hidden { transform: translateX(-100%); }
        }
        
        /* Scrollbar customizada para lista de agendamentos */
        #lista_agendamentos_data::-webkit-scrollbar {
            width: 6px;
        }
        #lista_agendamentos_data::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        #lista_agendamentos_data::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 10px;
        }
        #lista_agendamentos_data::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php include __DIR__ . '/../app/Views/partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto">
            
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="md:hidden text-gray-600">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">
                            <i class="fas fa-list mr-1 sm:mr-2 text-blue-600"></i>
                            <span class="hidden xs:inline">Agendamentos</span>
                            <span class="xs:hidden">Agenda</span>
                        </h2>
                    </div>
                    <button onclick="toggleModal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition flex items-center">
                        <i class="fas fa-plus"></i>
                        <span class="ml-2 hidden sm:inline">Novo Agendamento</span>
                        <span class="ml-2 sm:hidden">Novo</span>
                    </button>
                </div>
            </header>
            
            <!-- Conteúdo -->
            <div class="p-6 space-y-6">
                
                <!-- Mensagens -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-red-700"><?= sanitize($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($msg = flash('success')): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                            <p class="text-green-700"><?= sanitize($msg) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($msg = flash('error')): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                            <p class="text-red-700"><?= sanitize($msg) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Lista de Agendamentos -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    
                    <?php if (empty($agendamentos)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum agendamento ainda</h3>
                            <p class="text-gray-500 mb-6">Comece criando seu primeiro agendamento</p>
                            <button onclick="toggleModal()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition flex items-center">
                                <i class="fas fa-plus"></i>
                                <span class="ml-2 hidden sm:inline">Criar Primeiro Agendamento</span>
                                <span class="ml-2 sm:hidden">Criar Agendamento</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horário</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Tags</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($agendamentos as $agendamento): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <?php 
                                                    // Busca tag e foto do cliente
                                                    $tagCliente = null;
                                                    $fotoCliente = null;
                                                    $clienteIdTag = null;
                                                    
                                                    if (!empty($agendamento['cliente_id'])) {
                                                        $clienteIdTag = $agendamento['cliente_id'];
                                                    } else if (!empty($agendamento['aluno'])) {
                                                        $clientePorNome = $clienteModel->getByNome($agendamento['aluno'], Auth::id());
                                                        if (!empty($clientePorNome)) {
                                                            $clienteIdTag = $clientePorNome['id'];
                                                        }
                                                    }
                                                    
                                                    if ($clienteIdTag) {
                                                        // Busca foto
                                                        $clienteCompleto = $clienteModel->findById($clienteIdTag);
                                                        if ($clienteCompleto) {
                                                            $fotoCliente = $clienteCompleto['foto'] ?? null;
                                                        }
                                                        
                                                        // Busca tag
                                                        $tagsCliente = $tagModel->getByCliente($clienteIdTag);
                                                        if (!empty($tagsCliente)) {
                                                            $tagCliente = $tagsCliente[0];
                                                        }
                                                    }
                                                    ?>
                                                    
                                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
                                                        <?php if ($fotoCliente): ?>
                                                            <img src="<?= $fotoCliente ?>" class="w-full h-full object-cover" alt="<?= sanitize($agendamento['aluno']) ?>">
                                                        <?php else: ?>
                                                            <i class="fas fa-user text-blue-600"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <?php if ($tagCliente): ?>
                                                            <span class="inline-flex items-center text-xs font-medium" 
                                                                  style="color: <?= $tagCliente['cor'] ?>;">
                                                                <i class="fas <?= $tagCliente['icone'] ?>" style="font-size: 10px; margin-right: 4px;"></i>
                                                                <?= sanitize($tagCliente['nome']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <div class="font-medium text-gray-900" style="margin-top: 4px;">
                                                            <?= sanitize($agendamento['aluno']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?= formatDate($agendamento['data']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?= substr($agendamento['hora_inicio'], 0, 5) ?> - <?= substr($agendamento['hora_fim'], 0, 5) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php 
                                                    // Busca tag de SERVIÇO do agendamento
                                                    $tagServico = null;
                                                    if (!empty($agendamento['tag_servico_id'])) {
                                                        $tagServico = $tagModel->getByIdSimples($agendamento['tag_servico_id']);
                                                    }
                                                    
                                                    if ($tagServico): 
                                                    ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" 
                                                              style="background-color: <?= $tagServico['cor'] ?>20; color: <?= $tagServico['cor'] ?>; border: 1px solid <?= $tagServico['cor'] ?>40;">
                                                            <i class="fas <?= $tagServico['icone'] ?> mr-1" style="font-size: 10px;"></i>
                                                            <?= sanitize($tagServico['nome']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-xs text-gray-400">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?= sanitize($agendamento['descricao']) ?: '-' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="?editar=<?= $agendamento['id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                                    <input type="hidden" name="id" value="<?= $agendamento['id'] ?>">
                                                    <button type="submit" name="deletar" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </main>
        
    </div>
    
    <!-- Modal Criar/Editar -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-y-auto">
            
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-calendar-plus mr-2 text-blue-600"></i>
                        <?= $editando ? 'Editar' : 'Novo' ?> Agendamento
                    </h3>
                    <button onclick="toggleModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6">
                <div class="flex flex-col lg:flex-row gap-6">
                    
                    <!-- Coluna Esquerda: Formulário (80%) -->
                    <div class="flex-1 lg:w-4/5 space-y-6">
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                <?php endif; ?>
                
                <!-- Linha: Cliente (60%) | Data (40%) -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Seleção de Cliente (60% = 3 colunas) -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>
                            Cliente *
                        </label>
                        <div class="relative">
                            <input type="hidden" name="cliente_id" id="cliente_id" value="<?= $editando ? $editando['cliente_id'] : '' ?>">
                            <input type="text" 
                                   name="aluno" 
                                   id="nome_aluno" 
                                   required
                                   value="<?= $editando ? sanitize($editando['aluno']) : '' ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Digite o nome do cliente..."
                                   autocomplete="off">
                            <div id="cliente-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Digite para buscar ou <a href="/clientes/novo" target="_blank" class="text-blue-600 hover:underline">cadastrar novo cliente</a>
                        </p>
                    </div>
                    
                    <!-- Seleção de Data (40% = 2 colunas) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i>
                            Data da Aula *
                        </label>
                        <input type="date" name="data" id="data_agendamento" required
                               value="<?= $editando ? $editando['data'] : date('Y-m-d') ?>"
                               min="<?= date('Y-m-d') ?>"
                               onchange="atualizarHorariosDisponiveis()"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p id="alerta_data" class="text-xs mt-2 hidden"></p>
                        <button type="button" onclick="atualizarHorariosDisponiveis()" 
                                class="mt-2 text-xs text-blue-600 hover:text-blue-700">
                            🔄 Atualizar horários
                        </button>
                    </div>
                </div>
                
                <!-- Seleção de Horário -->
                <div id="container_horarios">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-clock mr-1"></i>
                            Horário da Aula *
                        </label>
                        <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" id="horario_personalizado" onchange="toggleHorarioPersonalizado()" 
                                   class="mr-2 w-4 h-4 text-blue-600 rounded">
                            Horário personalizado
                        </label>
                    </div>
                    
                    <!-- Horários Sugeridos (baseado na configuração) -->
                    <div id="horarios_sugeridos" class="space-y-2">
                        <p class="text-xs text-gray-500 mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Duração: <?= $duracaoAula ?> min | Intervalo: <?= $intervaloAula ?> min
                        </p>
                        <div id="lista_horarios" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 max-h-60 overflow-y-auto">
                            <div class="col-span-full text-center py-4 text-gray-400">
                                <i class="fas fa-clock text-2xl mb-2"></i>
                                <p class="text-sm">Selecione uma data para ver os horários disponíveis</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Horário Personalizado -->
                    <div id="horario_manual" class="hidden">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-3 rounded">
                            <p class="text-xs text-yellow-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <strong>Atenção:</strong> Horário personalizado ignora a duração padrão. Certifique-se de que não há conflitos.
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Início</label>
                                <input type="time" id="hora_inicio_manual" 
                                       onchange="validarHorarioPersonalizado()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Fim</label>
                                <input type="time" id="hora_fim_manual" 
                                       onchange="validarHorarioPersonalizado()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campos hidden para envio do formulário -->
                    <input type="hidden" name="hora_inicio" id="hora_inicio" value="<?= $editando ? substr($editando['hora_inicio'], 0, 5) : '' ?>">
                    <input type="hidden" name="hora_fim" id="hora_fim" value="<?= $editando ? substr($editando['hora_fim'], 0, 5) : '' ?>">
                    
                    <p id="alerta_horario" class="text-xs mt-2 hidden"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment mr-1"></i>
                        Descrição / Observações
                    </label>
                    <textarea name="descricao" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Informações adicionais sobre a aula..."><?= $editando ? sanitize($editando['descricao']) : '' ?></textarea>
                </div>
                
                <!-- Tag de Serviço (Tipo de Atendimento) -->
                <div>
                    <label for="tag_servico_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tags mr-1"></i>
                        Tipo de Serviço / Atendimento
                    </label>
                    
                    <?php if (empty($tagsServico)): ?>
                        <p class="text-gray-500 text-sm">
                            Nenhuma tag de serviço disponível. 
                            <a href="/tags" target="_blank" class="text-blue-600 hover:underline">Criar tags de serviço</a>
                        </p>
                    <?php else: ?>
                        <select name="tag_servico_id" 
                                id="tag_servico_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Sem classificação</option>
                            <?php foreach ($tagsServico as $tag): ?>
                                <option value="<?= $tag['id'] ?>" 
                                        <?= (isset($editando['tag_servico_id']) && $editando['tag_servico_id'] == $tag['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($tag['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Selecione o tipo de atendimento (Mentoria, Consultoria, Aula, etc)
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- ========================================= -->
                <!-- AGENDAMENTOS RECORRENTES - VERSÃO SIMPLES -->
                <!-- ========================================= -->
                
                <!-- Checkbox para ativar recorrência -->
                <div class="mb-4">
                    <label class="flex items-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg cursor-pointer hover:border-blue-400 transition">
                        <input type="checkbox" 
                               id="repetir_agendamento" 
                               name="repetir_agendamento"
                               class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500 mr-3">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-repeat mr-2 text-blue-600"></i>
                                Repetir este agendamento
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                Cria vários agendamentos de uma vez
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Container de recorrência SIMPLIFICADO -->
                <div id="container_recorrencia" class="hidden space-y-4 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border-2 border-blue-300">
                    
                    <div class="bg-white rounded-lg p-4 border border-blue-200">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-magic mr-2 text-blue-600"></i>
                            Configure a repetição
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Repetir a cada X dias -->
                            <div>
                                <label for="repetir_dias" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-days mr-1 text-blue-600"></i>
                                    Repetir a cada quantos dias?
                                </label>
                                <select id="repetir_dias" 
                                        name="repetir_dias"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg font-semibold">
                                    <option value="1">1 dia (todo dia)</option>
                                    <option value="2">2 dias</option>
                                    <option value="3">3 dias</option>
                                    <option value="7" selected>7 dias (toda semana)</option>
                                    <option value="14">14 dias (quinzenal)</option>
                                    <option value="30">30 dias (todo mês)</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Escolha o intervalo entre cada agendamento
                                </p>
                            </div>
                            
                            <!-- Quantas vezes -->
                            <div>
                                <label for="repetir_vezes" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-hashtag mr-1 text-blue-600"></i>
                                    Quantas vezes repetir?
                                </label>
                                <input type="number" 
                                       id="repetir_vezes" 
                                       name="repetir_vezes"
                                       min="2" 
                                       max="52" 
                                       value="10"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg font-semibold text-center">
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Número total de agendamentos a criar
                                </p>
                            </div>
                        </div>
                        
                        <!-- Preview automático -->
                        <div id="preview_simples" class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-sm font-semibold text-blue-900 mb-2">
                                <i class="fas fa-eye mr-1"></i>
                                Preview:
                            </p>
                            <p id="preview_texto" class="text-sm text-blue-800">
                                Serão criados <strong>10 agendamentos</strong>, um a cada <strong>7 dias</strong>
                            </p>
                        </div>
                    </div>
                    
                </div>
                <!-- FIM AGENDAMENTOS RECORRENTES -->
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="toggleModal()"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" name="salvar"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Agendamento
                    </button>
                </div>
                    </div>
                    <!-- Fim Coluna Esquerda -->
                    
                    <!-- Divisória Vertical -->
                    <div class="hidden lg:block w-px bg-gray-200"></div>
                    
                    <!-- Coluna Direita: Lista de Agendamentos (20%) -->
                    <div class="lg:w-1/5 min-w-[250px]">
                        <div class="sticky top-0">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 border-2 border-blue-200">
                                <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center justify-between">
                                    <span>
                                        <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                                        Agendamentos
                                    </span>
                                    <span id="contador_agendamentos" class="hidden inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-600 rounded-full">0</span>
                                </h4>
                                
                                <div id="lista_agendamentos_data" class="space-y-2 max-h-[calc(90vh-200px)] overflow-y-auto pr-2">
                                    <div class="text-center py-8 text-gray-400">
                                        <i class="fas fa-calendar-day text-3xl mb-2"></i>
                                        <p class="text-xs font-medium">Selecione uma data</p>
                                        <p class="text-xs text-gray-400 mt-1">para ver os agendamentos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!-- Fim Grid Principal -->
            </form>
            
        </div>
    </div>
    
    <!-- Sistema Inteligente de Agendamentos (Inline) -->
    <script>
        // Variáveis globais
        let disponibilidades = {};
        let agendamentosPorData = {};
        let duracaoAula = 60;
        let intervaloAula = 15;
        let horarioSelecionado = null;
        
        /**
         * Inicializa o sistema com dados do PHP
         */
        function inicializarSistema(disp, agend, duracao, intervalo) {
            disponibilidades = disp;
            agendamentosPorData = agend;
            duracaoAula = duracao;
            intervaloAula = intervalo;
            console.log('✅ Sistema inicializado');
        }
        
        /**
         * Atualiza horários disponíveis quando data é selecionada
         */
        function atualizarHorariosDisponiveis() {
            console.log('🔄 atualizarHorariosDisponiveis() chamada');
            
            const dataInput = document.getElementById('data_agendamento');
            const alertaData = document.getElementById('alerta_data');
            const containerHorarios = document.getElementById('container_horarios');
            
            if (!dataInput || !dataInput.value) {
                console.log('⚠️ Nenhuma data selecionada');
                return;
            }
            
            const dataSelecionada = new Date(dataInput.value + 'T00:00:00');
            const diaSemana = dataSelecionada.getDay();
            
            console.log('📅 Data selecionada:', dataInput.value, '| Dia da semana:', diaSemana);
            
            // Verifica se o dia da semana tem disponibilidade
            if (!disponibilidades[diaSemana] || disponibilidades[diaSemana].length === 0) {
                alertaData.textContent = '⚠️ Você não trabalha neste dia da semana. Escolha outra data.';
                alertaData.className = 'text-xs mt-2 text-red-600 font-medium';
                alertaData.classList.remove('hidden');
                dataInput.classList.add('border-red-500');
                
                // Limpa lista de horários
                const listaHorarios = document.getElementById('lista_horarios');
                listaHorarios.innerHTML = '<div class="col-span-full text-center py-4 text-red-500"><i class="fas fa-times-circle text-2xl mb-2"></i><p class="text-sm">Você não trabalha neste dia</p></div>';
                return;
            }
            
            // Data válida
            alertaData.textContent = '✅ Data disponível!';
            alertaData.className = 'text-xs mt-2 text-green-600 font-medium';
            alertaData.classList.remove('hidden');
            dataInput.classList.remove('border-red-500');
            dataInput.classList.add('border-green-500');
            
            // Gera horários disponíveis
            gerarHorariosDisponiveis(dataInput.value, diaSemana);
            
            // Atualiza lista de agendamentos existentes
            atualizarListaAgendamentosData(dataInput.value);
        }
        
        /**
         * Atualiza lista de agendamentos existentes na data selecionada
         */
        function atualizarListaAgendamentosData(data) {
            const listaAgendamentos = document.getElementById('lista_agendamentos_data');
            const agendamentosNaData = agendamentosPorData[data] || [];
            
            console.log('📋 Atualizando lista de agendamentos para', data, ':', agendamentosNaData);
            
            // Formata data para exibição
            const dataObj = new Date(data + 'T00:00:00');
            
            // Dia da semana
            const diaSemana = dataObj.toLocaleDateString('pt-BR', { weekday: 'long' });
            const diaSemanaCapitalizado = diaSemana.split('-').map(palavra => 
                palavra.charAt(0).toUpperCase() + palavra.slice(1)
            ).join('-');
            
            // Data completa
            const dia = dataObj.toLocaleDateString('pt-BR', { day: '2-digit' });
            const mes = dataObj.toLocaleDateString('pt-BR', { month: 'long' });
            const mesCapitalizado = mes.charAt(0).toUpperCase() + mes.slice(1);
            const ano = dataObj.toLocaleDateString('pt-BR', { year: 'numeric' });
            
            const dataLinha1 = diaSemanaCapitalizado;
            const dataLinha2 = `${dia} de ${mesCapitalizado} de ${ano}`;
            
            if (agendamentosNaData.length === 0) {
                listaAgendamentos.innerHTML = `
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-3 mb-3 shadow-sm">
                        <div class="text-center">
                            <p class="text-sm font-bold text-white">
                                <i class="fas fa-calendar-day mr-1"></i>
                                ${dataLinha1}
                            </p>
                            <p class="text-xs text-orange-100 mt-0.5">
                                ${dataLinha2}
                            </p>
                        </div>
                    </div>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-check-circle text-3xl mb-2 text-green-400"></i>
                        <p class="text-xs font-bold text-green-600">Nenhum agendamento</p>
                        <p class="text-xs text-gray-500 mt-1">Esta data está livre</p>
                    </div>
                `;
                
                // Esconde contador
                const contador = document.getElementById('contador_agendamentos');
                if (contador) contador.classList.add('hidden');
                
                return;
            }
            
            // Ordena por horário
            agendamentosNaData.sort((a, b) => a.inicio.localeCompare(b.inicio));
            
            // Cabeçalho com a data
            let html = `
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-3 mb-3 shadow-sm">
                    <div class="text-center">
                        <p class="text-sm font-bold text-white">
                            <i class="fas fa-calendar-day mr-1"></i>
                            ${dataLinha1}
                        </p>
                        <p class="text-xs text-orange-100 mt-0.5">
                            ${dataLinha2}
                        </p>
                    </div>
                </div>
            `;
            
            agendamentosNaData.forEach((agendamento, index) => {
                const duracao = calcularDuracaoTexto(agendamento.inicio, agendamento.fim);
                
                html += `
                    <div class="bg-white rounded-lg p-3 border border-gray-200 hover:border-blue-300 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <i class="fas fa-user text-blue-600 text-xs"></i>
                                    <p class="text-sm font-semibold text-gray-800 truncate">${agendamento.aluno}</p>
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-gray-600">
                                    <i class="fas fa-clock text-gray-400"></i>
                                    <span class="font-medium">${agendamento.inicio} - ${agendamento.fim}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-hourglass-half text-gray-400"></i>
                                    ${duracao}
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs font-bold">
                                    ${index + 1}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Atualiza contador
            const contador = document.getElementById('contador_agendamentos');
            if (contador) {
                contador.textContent = agendamentosNaData.length;
                contador.classList.remove('hidden');
            }
            
            listaAgendamentos.innerHTML = html;
        }
        
        /**
         * Calcula duração em texto
         */
        function calcularDuracaoTexto(inicio, fim) {
            const [hI, mI] = inicio.split(':').map(Number);
            const [hF, mF] = fim.split(':').map(Number);
            
            const minutosInicio = hI * 60 + mI;
            const minutosFim = hF * 60 + mF;
            const diffMins = minutosFim - minutosInicio;
            
            const horas = Math.floor(diffMins / 60);
            const minutos = diffMins % 60;
            
            if (horas > 0 && minutos > 0) {
                return `${horas}h ${minutos}min`;
            } else if (horas > 0) {
                return `${horas}h`;
            } else {
                return `${minutos}min`;
            }
        }
        
        /**
         * Gera slots de horários baseado na configuração do professor
         */
        function gerarHorariosDisponiveis(data, diaSemana) {
            console.log('📊 Gerando horários para dia', diaSemana);
            console.log('Disponibilidades recebidas:', disponibilidades);
            console.log('Duração aula:', duracaoAula, '| Intervalo:', intervaloAula);
            
            const listaHorarios = document.getElementById('lista_horarios');
            listaHorarios.innerHTML = '';
            
            const intervalosDisponiveis = disponibilidades[diaSemana];
            const agendamentosNaData = agendamentosPorData[data] || [];
            
            console.log('Intervalos disponíveis para dia', diaSemana, ':', intervalosDisponiveis);
            console.log('Agendamentos na data', data, ':', agendamentosNaData);
            
            if (!intervalosDisponiveis || intervalosDisponiveis.length === 0) {
                console.error('❌ Nenhum intervalo disponível para este dia!');
                listaHorarios.innerHTML = '<div class="col-span-full text-center py-4 text-red-500"><i class="fas fa-times-circle text-2xl mb-2"></i><p class="text-sm">Erro: Nenhuma disponibilidade configurada</p></div>';
                return;
            }
            
            // Verifica se é hoje para bloquear horários passados
            const hoje = new Date();
            const dataHoje = hoje.toISOString().split('T')[0];
            const ehHoje = (data === dataHoje);
            const horaAtualMinutos = ehHoje ? (hoje.getHours() * 60 + hoje.getMinutes()) : 0;
            
            console.log('🕒 Hora atual:', hoje.getHours() + ':' + hoje.getMinutes(), '(' + horaAtualMinutos + ' minutos) | É hoje?', ehHoje);
            
            if (ehHoje) {
                console.log('⚠️ Como é hoje, horários antes de', hoje.getHours() + ':' + hoje.getMinutes(), 'serão bloqueados');
            }
            
            let totalSlots = 0;
            
            // Para cada intervalo de disponibilidade do dia
            console.log('🔄 Processando', intervalosDisponiveis.length, 'intervalos...');
            
            intervalosDisponiveis.forEach((intervalo, index) => {
                console.log(`🔹 Intervalo ${index + 1}:`, intervalo);
                const slots = gerarSlots(intervalo.inicio, intervalo.fim, duracaoAula, intervaloAula);
                console.log(`  → ${intervalo.inicio}-${intervalo.fim}: ${slots.length} slots gerados`);
                console.log('  Slots:', slots);
                
                slots.forEach(slot => {
                    // Converte horário do slot para minutos
                    const [horaSlot, minutoSlot] = slot.inicio.split(':').map(Number);
                    const slotMinutos = horaSlot * 60 + minutoSlot;
                    
                    // Verifica se o horário já passou (apenas se for hoje)
                    const horarioPassou = ehHoje && slotMinutos < horaAtualMinutos;
                    
                    // Verifica se o slot está livre (sem conflito)
                    const temConflito = verificarConflito(slot.inicio, slot.fim, agendamentosNaData);
                    
                    // Define se o slot está disponível
                    const indisponivel = horarioPassou || temConflito;
                    
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = indisponivel
                        ? 'px-3 py-2 text-xs border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed'
                        : 'px-3 py-2 text-xs border-2 border-blue-300 rounded-lg text-gray-700 hover:bg-blue-50 hover:border-blue-500 hover:text-gray-900 transition';
                    
                    let statusTexto = '✅ Livre';
                    let statusCor = 'text-gray-500';
                    let tituloTooltip = '';
                    
                    if (horarioPassou) {
                        statusTexto = '⏰ Passou';
                        statusCor = 'text-orange-500';
                        tituloTooltip = 'Horário já passou';
                    } else if (temConflito) {
                        statusTexto = '❌ Ocupado';
                        statusCor = 'text-red-500';
                        tituloTooltip = `Ocupado: ${temConflito.aluno}`;
                    }
                    
                    button.innerHTML = `
                        <div class="font-semibold">${slot.inicio} - ${slot.fim}</div>
                        <div class="text-[10px] ${statusCor}">
                            ${statusTexto}
                        </div>
                    `;
                    
                    if (!indisponivel) {
                        button.onclick = () => selecionarHorario(slot.inicio, slot.fim, button);
                        totalSlots++;
                    } else {
                        button.disabled = true;
                        button.title = tituloTooltip;
                    }
                    
                    listaHorarios.appendChild(button);
                });
            });
            
            console.log(`✅ Total de slots livres: ${totalSlots}`);
            
            // Se não há slots disponíveis
            if (totalSlots === 0) {
                let mensagem = '';
                let icone = '';
                
                if (ehHoje) {
                    // Se for hoje, provavelmente os horários já passaram
                    icone = 'fa-clock';
                    mensagem = `
                        <p class="text-sm font-medium">Não há mais horários disponíveis hoje.</p>
                        <p class="text-xs mt-1">Todos os horários já passaram ou estão ocupados.</p>
                        <p class="text-xs mt-2 text-blue-600">💡 Tente agendar para amanhã ou outro dia.</p>
                    `;
                } else {
                    // Se for outro dia, todos estão ocupados
                    icone = 'fa-calendar-times';
                    mensagem = `
                        <p class="text-sm font-medium">Não há horários disponíveis nesta data.</p>
                        <p class="text-xs mt-1">Todos os horários estão ocupados.</p>
                    `;
                }
                
                listaHorarios.innerHTML = `
                    <div class="col-span-full text-center py-6 text-gray-500">
                        <i class="fas ${icone} text-3xl mb-2"></i>
                        ${mensagem}
                    </div>
                `;
            }
        }
        
        /**
         * Gera slots de horário com base na duração e intervalo
         */
        function gerarSlots(horaInicio, horaFim, duracao, intervalo) {
            const slots = [];
            let [horaAtual, minutoAtual] = horaInicio.split(':').map(Number);
            const [horaFinal, minutoFinal] = horaFim.split(':').map(Number);
            
            const minutosInicio = horaAtual * 60 + minutoAtual;
            const minutosFim = horaFinal * 60 + minutoFinal;
            
            let minutosAtual = minutosInicio;
            
            while (minutosAtual + duracao <= minutosFim) {
                const inicio = minutosParaHora(minutosAtual);
                const fim = minutosParaHora(minutosAtual + duracao);
                
                slots.push({ inicio, fim });
                
                // Próximo slot = duração da aula + intervalo
                minutosAtual += duracao + intervalo;
            }
            
            return slots;
        }
        
        /**
         * Converte minutos para formato HH:MM
         */
        function minutosParaHora(minutos) {
            const horas = Math.floor(minutos / 60);
            const mins = minutos % 60;
            return `${String(horas).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
        }
        
        /**
         * Verifica se há conflito de horário
         */
        function verificarConflito(inicio, fim, agendamentos) {
            for (const ag of agendamentos) {
                if (
                    (inicio >= ag.inicio && inicio < ag.fim) ||
                    (fim > ag.inicio && fim <= ag.fim) ||
                    (inicio <= ag.inicio && fim >= ag.fim)
                ) {
                    return ag;
                }
            }
            return null;
        }
        
        /**
         * Seleciona um horário da lista
         */
        function selecionarHorario(inicio, fim, botao) {
            // Remove seleção anterior
            document.querySelectorAll('#lista_horarios button').forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-600', '!text-white');
                btn.classList.add('border-blue-300');
            });
            
            // Adiciona seleção ao botão clicado
            botao.classList.add('bg-blue-500', 'text-white', 'border-blue-600', '!text-white');
            botao.classList.remove('border-blue-300', 'hover:text-gray-900');
            
            // Atualiza campos hidden
            document.getElementById('hora_inicio').value = inicio;
            document.getElementById('hora_fim').value = fim;
            
            horarioSelecionado = { inicio, fim };
            
            // Mostra confirmação
            const alertaHorario = document.getElementById('alerta_horario');
            alertaHorario.textContent = `✅ Horário selecionado: ${inicio} - ${fim}`;
            alertaHorario.className = 'text-xs mt-2 text-green-600 font-medium';
            alertaHorario.classList.remove('hidden');
            
            console.log('✅ Horário selecionado:', inicio, '-', fim);
        }
        
        /**
         * Toggle entre horário sugerido e personalizado
         */
        function toggleHorarioPersonalizado() {
            const checkbox = document.getElementById('horario_personalizado');
            const horariosSugeridos = document.getElementById('horarios_sugeridos');
            const horarioManual = document.getElementById('horario_manual');
            
            if (checkbox.checked) {
                horariosSugeridos.classList.add('hidden');
                horarioManual.classList.remove('hidden');
                document.getElementById('hora_inicio_manual').focus();
            } else {
                horariosSugeridos.classList.remove('hidden');
                horarioManual.classList.add('hidden');
                document.getElementById('hora_inicio_manual').value = '';
                document.getElementById('hora_fim_manual').value = '';
            }
        }
        
        /**
         * Valida horário personalizado
         */
        function validarHorarioPersonalizado() {
            const inicioManual = document.getElementById('hora_inicio_manual').value;
            const fimManual = document.getElementById('hora_fim_manual').value;
            const alertaHorario = document.getElementById('alerta_horario');
            const dataInput = document.getElementById('data_agendamento');
            
            if (!inicioManual || !fimManual) return;
            
            // Validação 1: Fim deve ser maior que início
            if (fimManual <= inicioManual) {
                alertaHorario.textContent = '⚠️ Horário de fim deve ser maior que o de início.';
                alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
                alertaHorario.classList.remove('hidden');
                return;
            }
            
            // Validação 2: Verifica se está dentro da disponibilidade
            const dataSelecionada = new Date(dataInput.value + 'T00:00:00');
            const diaSemana = dataSelecionada.getDay();
            const intervalosDisponiveis = disponibilidades[diaSemana] || [];
            
            console.log('🔍 Validando horário personalizado:', inicioManual, '-', fimManual);
            console.log('Disponibilidades do dia', diaSemana, ':', intervalosDisponiveis);
            
            if (intervalosDisponiveis.length === 0) {
                alertaHorario.textContent = '❌ Você não trabalha neste dia da semana.';
                alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
                alertaHorario.classList.remove('hidden');
                return;
            }
            
            // Verifica se o horário está COMPLETAMENTE dentro de algum intervalo disponível
            let dentroDisponibilidade = false;
            let intervaloValido = null;
            
            for (const intervalo of intervalosDisponiveis) {
                // O horário personalizado deve estar COMPLETAMENTE dentro do intervalo
                if (inicioManual >= intervalo.inicio && fimManual <= intervalo.fim) {
                    dentroDisponibilidade = true;
                    intervaloValido = intervalo;
                    break;
                }
            }
            
            if (!dentroDisponibilidade) {
                const horariosDisponiveis = intervalosDisponiveis.map(h => `${h.inicio}-${h.fim}`).join(', ');
                alertaHorario.textContent = `❌ Horário fora da sua disponibilidade. Você trabalha: ${horariosDisponiveis}`;
                alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
                alertaHorario.classList.remove('hidden');
                console.log('❌ Horário fora da disponibilidade');
                return;
            }
            
            console.log('✅ Horário dentro da disponibilidade:', intervaloValido);
            
            // Validação 3: Verifica se o horário já passou (se for hoje)
            const hoje = new Date();
            const dataHoje = hoje.toISOString().split('T')[0];
            const ehHoje = (dataInput.value === dataHoje);
            
            if (ehHoje) {
                const horaAtualMinutos = hoje.getHours() * 60 + hoje.getMinutes();
                const [horaInicio, minutoInicio] = inicioManual.split(':').map(Number);
                const inicioMinutos = horaInicio * 60 + minutoInicio;
                
                if (inicioMinutos < horaAtualMinutos) {
                    alertaHorario.textContent = '❌ Horário de início já passou. Escolha um horário futuro.';
                    alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
                    alertaHorario.classList.remove('hidden');
                    return;
                }
            }
            
            // Validação 4: Verifica conflitos com agendamentos existentes
            const agendamentosNaData = agendamentosPorData[dataInput.value] || [];
            const conflito = verificarConflito(inicioManual, fimManual, agendamentosNaData);
            
            if (conflito) {
                alertaHorario.textContent = `❌ Conflito com agendamento: ${conflito.aluno} (${conflito.inicio}-${conflito.fim})`;
                alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
                alertaHorario.classList.remove('hidden');
                console.log('❌ Conflito detectado:', conflito);
                return;
            }
            
            // Tudo OK! Horário válido
            alertaHorario.textContent = '✅ Horário personalizado válido!';
            alertaHorario.className = 'text-xs mt-2 text-green-600 font-medium';
            alertaHorario.classList.remove('hidden');
            
            // Atualiza campos hidden
            document.getElementById('hora_inicio').value = inicioManual;
            document.getElementById('hora_fim').value = fimManual;
            
            console.log('✅ Horário personalizado validado:', inicioManual, '-', fimManual);
        }
        
        /**
         * Limpa seleção ao fechar modal e reseta formulário
         */
        function limparSelecao() {
            // Reseta variáveis globais
            horarioSelecionado = null;
            
            // Limpa todos os campos do formulário
            const form = document.getElementById('form_agendamento');
            if (form) {
                form.reset();
            }
            
            // Limpa campos específicos
            document.getElementById('hora_inicio').value = '';
            document.getElementById('hora_fim').value = '';
            document.getElementById('data_agendamento').value = '';
            
            // Limpa campo de cliente se existir
            const clienteInput = document.getElementById('cliente_id');
            if (clienteInput) {
                clienteInput.value = '';
            }
            
            // Limpa campo de aluno se existir
            const alunoInput = document.getElementById('aluno');
            if (alunoInput) {
                alunoInput.value = '';
            }
            
            // Limpa descrição/observações
            const descricaoInput = document.getElementById('descricao');
            if (descricaoInput) {
                descricaoInput.value = '';
            }
            
            // Limpa ID de edição se existir
            const idInput = document.getElementById('agendamento_id');
            if (idInput) {
                idInput.value = '';
            }
            
            // Oculta alertas
            const alertaData = document.getElementById('alerta_data');
            const alertaHorario = document.getElementById('alerta_horario');
            
            if (alertaData) alertaData.classList.add('hidden');
            if (alertaHorario) alertaHorario.classList.add('hidden');
            
            // Reseta horário personalizado
            const horarioPersonalizado = document.getElementById('horario_personalizado');
            if (horarioPersonalizado) {
                horarioPersonalizado.checked = false;
            }
            
            const horariosSugeridos = document.getElementById('horarios_sugeridos');
            const horarioManual = document.getElementById('horario_manual');
            
            if (horariosSugeridos) horariosSugeridos.classList.remove('hidden');
            if (horarioManual) horarioManual.classList.add('hidden');
            
            // Limpa lista de horários disponíveis
            const listaHorarios = document.getElementById('lista_horarios');
            if (listaHorarios) {
                listaHorarios.innerHTML = '';
            }
            
            // Define data atual para novo agendamento
            const dataInput = document.getElementById('data_agendamento');
            if (dataInput && !dataInput.value) {
                const hoje = new Date();
                const dataFormatada = hoje.toISOString().split('T')[0];
                dataInput.value = dataFormatada;
                console.log('📅 Data atual definida:', dataFormatada);
                
                // Carrega horários disponíveis para hoje
                setTimeout(() => {
                    if (typeof atualizarHorariosDisponiveis === 'function') {
                        atualizarHorariosDisponiveis();
                    }
                }, 100);
            }
            
            // Atualiza título do modal para "Novo Agendamento"
            const tituloModal = document.querySelector('#modal h2');
            if (tituloModal) {
                tituloModal.innerHTML = '<i class="fas fa-calendar-plus mr-2"></i> Novo Agendamento';
            }
            
            console.log('🧹 Formulário completamente limpo e resetado');
        }
        
        console.log('🚀 Sistema de agendamentos carregado (inline)')
        
        // Inicializa sistema com dados do PHP
        if (typeof inicializarSistema === 'function') {
            inicializarSistema(
                <?= json_encode($disponibilidadesPorDia) ?>,
                <?= json_encode($agendamentosPorData) ?>,
                <?= $duracaoAula ?>,
                <?= $intervaloAula ?>
            );
        } else {
            console.error('❌ Função inicializarSistema não encontrada!');
            console.log('Tentando carregar inline...');
            // Define variáveis globalmente como fallback
            window.disponibilidades = <?= json_encode($disponibilidadesPorDia) ?>;
            window.agendamentosPorData = <?= json_encode($agendamentosPorData) ?>;
            window.duracaoAula = <?= $duracaoAula ?>;
            window.intervaloAula = <?= $intervaloAula ?>;
        }
        
        const dispData = <?= json_encode($disponibilidadesPorDia) ?>;
        console.log('✅ Sistema inicializado com:', {
            disponibilidades: dispData,
            duracaoAula: <?= $duracaoAula ?>,
            intervaloAula: <?= $intervaloAula ?>
        });
        
        // Debug: Mostra quais dias têm disponibilidade
        console.log('📅 Dias com disponibilidade configurada:');
        for (let dia in dispData) {
            const nomeDia = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][dia];
            console.log(`  ${nomeDia} (${dia}):`, dispData[dia]);
        }
        
        if (Object.keys(dispData).length === 0) {
            console.warn('⚠️ ATENÇÃO: Nenhuma disponibilidade configurada!');
            console.log('👉 Configure sua agenda em "Minha Agenda" primeiro');
        }
        
        // Funções auxiliares
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
        
        function toggleModal() {
            const modal = document.getElementById('modal');
            const isOpening = modal.classList.contains('hidden');
            
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
            
            // Limpa seleção ao fechar
            if (modal.classList.contains('hidden')) {
                limparSelecao();
                
                // Remove parâmetros da URL ao fechar o modal
                const url = new URL(window.location);
                url.searchParams.delete('editar');
                url.searchParams.delete('data');
                window.history.replaceState({}, '', url);
            } else if (isOpening) {
                // Ao abrir modal, verifica se é para NOVO ou EDITAR
                const url = new URL(window.location);
                const editarId = url.searchParams.get('editar');
                
                // Se NÃO está editando, limpa completamente o formulário
                if (!editarId) {
                    console.log('🆕 Abrindo modal para NOVO agendamento - resetando formulário');
                    limparSelecao();
                    
                    // Garante que o título está correto
                    const tituloModal = document.querySelector('#modal h2');
                    if (tituloModal) {
                        tituloModal.innerHTML = '<i class="fas fa-calendar-plus mr-2"></i> Novo Agendamento';
                    }
                }
                
                // Ao abrir, se já tem data selecionada, mostra horários
                const dataInput = document.getElementById('data_agendamento');
                if (dataInput && dataInput.value) {
                    setTimeout(() => atualizarHorariosDisponiveis(), 100);
                }
            }
        }
        
        // Carrega horários automaticamente se já tem data
        document.addEventListener('DOMContentLoaded', function() {
            const dataInput = document.getElementById('data_agendamento');
            if (dataInput && dataInput.value) {
                console.log('📅 Data pré-selecionada detectada:', dataInput.value);
                if (typeof atualizarHorariosDisponiveis === 'function') {
                    atualizarHorariosDisponiveis();
                    
                    // Se estiver editando, seleciona o horário atual após carregar
                    setTimeout(function() {
                        selecionarHorarioEditando();
                    }, 500);
                } else {
                    console.error('❌ Função atualizarHorariosDisponiveis não encontrada!');
                    alert('⚠️ Erro: Sistema de horários não carregou. Recarregue a página (Ctrl+Shift+R)');
                }
            }
        });
        
        /**
         * Seleciona automaticamente o horário do agendamento sendo editado
         */
        function selecionarHorarioEditando() {
            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFim = document.getElementById('hora_fim').value;
            
            if (!horaInicio || !horaFim) return;
            
            console.log('🔍 Procurando horário para seleção:', horaInicio, '-', horaFim);
            
            // Procura o botão correspondente ao horário
            const botoes = document.querySelectorAll('#lista_horarios button');
            botoes.forEach(botao => {
                const texto = botao.textContent;
                const horarioMatch = texto.match(/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/);
                
                if (horarioMatch) {
                    const btnInicio = horarioMatch[1];
                    const btnFim = horarioMatch[2];
                    
                    if (btnInicio === horaInicio && btnFim === horaFim) {
                        console.log('✅ Horário encontrado! Selecionando...');
                        
                        // Remove seleção anterior
                        botoes.forEach(btn => {
                            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-600', 'ring-4', 'ring-blue-300');
                        });
                        
                        // Adiciona seleção com destaque especial (editando)
                        botao.classList.remove('border-blue-300', 'border-gray-300');
                        botao.classList.add('bg-blue-500', 'text-white', 'border-blue-600', 'ring-4', 'ring-blue-300');
                        
                        // Adiciona badge "Editando"
                        const divs = botao.querySelectorAll('div');
                        if (divs.length > 1) {
                            const statusDiv = divs[1];
                            statusDiv.innerHTML = '<i class="fas fa-edit"></i> Editando';
                            statusDiv.className = 'text-[10px] text-white font-bold';
                        }
                        
                        // Scroll para o botão
                        botao.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            });
        }
        
        <?php if ($editando): ?>
            // Abre modal automaticamente se estiver editando
            toggleModal();
        <?php endif; ?>
        
        <?php if (isset($_GET['novo']) && $_GET['novo'] == '1'): ?>
            // Abre modal automaticamente se vier do dashboard
            document.addEventListener('DOMContentLoaded', function() {
                toggleModal();
            });
        <?php endif; ?>
        
        // ============================================
        // AUTOCOMPLETE DE CLIENTES
        // ============================================
        
        const nomeAlunoInput = document.getElementById('nome_aluno');
        const clienteIdInput = document.getElementById('cliente_id');
        const suggestionsDiv = document.getElementById('cliente-suggestions');
        let debounceTimer;
        
        nomeAlunoInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 2) {
                suggestionsDiv.classList.add('hidden');
                return;
            }
            
            debounceTimer = setTimeout(() => {
                // Busca clientes via fetch (simulado - você pode criar uma API)
                fetch(`/api/clientes/buscar?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(clientes => {
                        if (clientes.length === 0) {
                            suggestionsDiv.innerHTML = '<div class="p-3 text-gray-500 text-sm">Nenhum cliente encontrado</div>';
                            suggestionsDiv.classList.remove('hidden');
                            return;
                        }
                        
                        let html = '';
                        clientes.forEach(cliente => {
                            html += `
                                <div class="p-3 hover:bg-gray-100 cursor-pointer border-b last:border-b-0" 
                                     onclick="selecionarCliente(${cliente.id}, '${cliente.nome.replace(/'/g, "\\'")}')">
                                    <div class="font-medium text-gray-900">${cliente.nome}</div>
                                    <div class="text-xs text-gray-500">
                                        ${cliente.email || ''} ${cliente.telefone ? '• ' + cliente.telefone : ''}
                                    </div>
                                </div>
                            `;
                        });
                        
                        suggestionsDiv.innerHTML = html;
                        suggestionsDiv.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Erro ao buscar clientes:', error);
                    });
            }, 300);
        });
        
        // Função global para selecionar cliente
        window.selecionarCliente = function(id, nome) {
            clienteIdInput.value = id;
            nomeAlunoInput.value = nome;
            suggestionsDiv.classList.add('hidden');
        };
        
        // Fecha sugestões ao clicar fora
        document.addEventListener('click', function(e) {
            if (!nomeAlunoInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.add('hidden');
            }
        });
    </script>
    
    <!-- Agendamento Recorrente JS -->
    <script src="/assets/js/agendamento-recorrente.js?v=<?= time() ?>"></script>
    
    <script>
    // ========================================
    // AGENDAMENTOS RECORRENTES - VERSÃO SIMPLES
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxRepetir = document.getElementById('repetir_agendamento');
        const containerRecorrencia = document.getElementById('container_recorrencia');
        const selectDias = document.getElementById('repetir_dias');
        const inputVezes = document.getElementById('repetir_vezes');
        const previewTexto = document.getElementById('preview_texto');
        
        // Toggle ao marcar checkbox
        if (checkboxRepetir && containerRecorrencia) {
            checkboxRepetir.addEventListener('change', function() {
                if (this.checked) {
                    containerRecorrencia.classList.remove('hidden');
                    atualizarPreview();
                } else {
                    containerRecorrencia.classList.add('hidden');
                }
            });
        }
        
        // Atualiza preview ao mudar valores
        function atualizarPreview() {
            const dias = selectDias ? selectDias.value : 7;
            const vezes = inputVezes ? inputVezes.value : 10;
            
            let intervalo = '';
            if (dias == 1) intervalo = 'todo dia';
            else if (dias == 7) intervalo = 'toda semana';
            else if (dias == 14) intervalo = 'quinzenalmente';
            else if (dias == 30) intervalo = 'todo mês';
            else intervalo = `a cada ${dias} dias`;
            
            if (previewTexto) {
                previewTexto.innerHTML = `Serão criados <strong>${vezes} agendamentos</strong>, um ${intervalo}`;
            }
        }
        
        // Atualiza preview ao mudar campos
        if (selectDias) {
            selectDias.addEventListener('change', atualizarPreview);
        }
        if (inputVezes) {
            inputVezes.addEventListener('input', atualizarPreview);
        }
    });
    </script>
    
</body>
</html>
