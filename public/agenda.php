<?php
/**
 * Página de Configuração de Agenda - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 * Versão: 1.0.1 - Suporte a múltiplos intervalos
 * Última atualização: 30/10/2025 10:35
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Configuracao.php';
require_once __DIR__ . '/../app/Models/Disponibilidade.php';

Auth::requireAuth();

$user = Auth::user();
$configuracaoModel = new Configuracao();
$disponibilidadeModel = new Disponibilidade();

// Busca configurações atuais
$config = $configuracaoModel->getByProfessor(Auth::id());
$disponibilidades = $disponibilidadeModel->getByProfessor(Auth::id());

// Agrupa disponibilidades por dia
$disponibilidadesPorDia = [];
foreach ($disponibilidades as $disp) {
    $disponibilidadesPorDia[$disp['dia_semana']][] = $disp;
}

$errors = [];
$success = false;

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['salvar_config'])) {
        $duracaoAula = intval($_POST['duracao_aula'] ?? 60);
        $intervalo = intval($_POST['intervalo'] ?? 15);
        
        if ($duracaoAula < 15 || $duracaoAula > 240) {
            $errors[] = 'Duração da aula deve estar entre 15 e 240 minutos.';
        }
        
        if ($intervalo < 0 || $intervalo > 60) {
            $errors[] = 'Intervalo deve estar entre 0 e 60 minutos.';
        }
        
        if (empty($errors)) {
            $configuracaoModel->save(Auth::id(), [
                'duracao_aula' => $duracaoAula,
                'intervalo' => $intervalo
            ]);
            
            setFlash('success', 'Configurações salvas com sucesso!');
            redirect('/agenda');
        }
    }
    
    if (isset($_POST['salvar_disponibilidade'])) {
        $diasSelecionados = $_POST['dias'] ?? [];
        
        if (empty($diasSelecionados)) {
            $errors[] = 'Selecione pelo menos um dia da semana.';
        }
        
        // Remove disponibilidades antigas
        $disponibilidadeModel->deleteByProfessor(Auth::id());
        
        // Adiciona novas disponibilidades (suporta múltiplos intervalos)
        foreach ($diasSelecionados as $dia) {
            // Verifica se há múltiplos intervalos para este dia
            $horariosInicio = $_POST["hora_inicio_{$dia}"] ?? [];
            $horariosFim = $_POST["hora_fim_{$dia}"] ?? [];
            
            // Se for array, processa múltiplos intervalos
            if (is_array($horariosInicio) && is_array($horariosFim)) {
                $count = min(count($horariosInicio), count($horariosFim));
                
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($horariosInicio[$i]) && !empty($horariosFim[$i])) {
                        $disponibilidadeModel->create([
                            'professor_id' => Auth::id(),
                            'dia_semana' => $dia,
                            'hora_inicio' => $horariosInicio[$i],
                            'hora_fim' => $horariosFim[$i]
                        ]);
                    }
                }
            }
        }
        
        setFlash('success', 'Disponibilidade atualizada com sucesso!');
        redirect('/agenda');
    }
}

$diasSemana = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Minha Agenda - Agenda do Professor Inteligente</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) {
            .sidebar.hidden { transform: translateX(-100%); }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar (mesmo do dashboard) -->
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
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                            Minha Agenda
                        </h2>
                    </div>
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
                
                <!-- Configurações Gerais -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-cog mr-2 text-blue-600"></i>
                        Configurações de Aula
                    </h3>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock mr-1"></i>
                                    Duração Padrão da Aula (minutos)
                                </label>
                                <input type="number" name="duracao_aula" 
                                       value="<?= $config['duracao_aula'] ?? 60 ?>"
                                       min="15" max="240" step="5" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Entre 15 e 240 minutos</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-pause mr-1"></i>
                                    Intervalo Entre Aulas (minutos)
                                </label>
                                <input type="number" name="intervalo" 
                                       value="<?= $config['intervalo'] ?? 15 ?>"
                                       min="0" max="60" step="5" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Entre 0 e 60 minutos</p>
                            </div>
                            
                        </div>
                        
                        <button type="submit" name="salvar_config"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>
                            Salvar Configurações
                        </button>
                    </form>
                </div>
                
                <!-- Disponibilidade Semanal -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-calendar-week mr-2 text-blue-600"></i>
                        Disponibilidade Semanal
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        
                        <?php foreach ($diasSemana as $numero => $nome): ?>
                            <?php 
                            $temDisponibilidade = isset($disponibilidadesPorDia[$numero]);
                            $intervalos = $temDisponibilidade ? $disponibilidadesPorDia[$numero] : [];
                            ?>
                            
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" name="dias[]" value="<?= $numero ?>"
                                               <?= $temDisponibilidade ? 'checked' : '' ?>
                                               onchange="toggleDia(<?= $numero ?>)"
                                               class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                        <span class="font-semibold text-gray-800"><?= $nome ?></span>
                                    </label>
                                    <button type="button" onclick="adicionarIntervalo(<?= $numero ?>)" 
                                            id="btn_add_<?= $numero ?>" 
                                            class="<?= !$temDisponibilidade ? 'hidden' : '' ?> text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        <i class="fas fa-plus-circle mr-1"></i>
                                        Adicionar Intervalo
                                    </button>
                                </div>
                                
                                <div id="horarios_<?= $numero ?>" 
                                     class="space-y-3 <?= !$temDisponibilidade ? 'hidden' : '' ?>">
                                    
                                    <?php if (!empty($intervalos)): ?>
                                        <?php foreach ($intervalos as $index => $intervalo): ?>
                                            <div class="intervalo-item grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <label class="block text-sm text-gray-600 mb-1">Hora Início</label>
                                                    <input type="time" name="hora_inicio_<?= $numero ?>[]" 
                                                           value="<?= substr($intervalo['hora_inicio'], 0, 5) ?>"
                                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-600 mb-1">Hora Fim</label>
                                                    <div class="flex gap-2">
                                                        <input type="time" name="hora_fim_<?= $numero ?>[]" 
                                                               value="<?= substr($intervalo['hora_fim'], 0, 5) ?>"
                                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                        <?php if ($index > 0): ?>
                                                            <button type="button" onclick="removerIntervalo(this)" 
                                                                    class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="intervalo-item grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Hora Início</label>
                                                <input type="time" name="hora_inicio_<?= $numero ?>[]" 
                                                       value="08:00"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Hora Fim</label>
                                                <input type="time" name="hora_fim_<?= $numero ?>[]" 
                                                       value="18:00"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                        
                        <button type="submit" name="salvar_disponibilidade"
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>
                            Salvar Disponibilidade
                        </button>
                    </form>
                </div>
                
            </div>
            
        </main>
        
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
        
        function toggleDia(dia) {
            const checkbox = document.querySelector(`input[value="${dia}"]`);
            const horarios = document.getElementById(`horarios_${dia}`);
            const btnAdd = document.getElementById(`btn_add_${dia}`);
            
            if (checkbox.checked) {
                horarios.classList.remove('hidden');
                btnAdd.classList.remove('hidden');
            } else {
                horarios.classList.add('hidden');
                btnAdd.classList.add('hidden');
            }
        }
        
        function adicionarIntervalo(dia) {
            const container = document.getElementById(`horarios_${dia}`);
            const novoIntervalo = document.createElement('div');
            novoIntervalo.className = 'intervalo-item grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-gray-50 rounded-lg';
            novoIntervalo.innerHTML = `
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Hora Início</label>
                    <input type="time" name="hora_inicio_${dia}[]" 
                           value="08:00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Hora Fim</label>
                    <div class="flex gap-2">
                        <input type="time" name="hora_fim_${dia}[]" 
                               value="18:00"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="removerIntervalo(this)" 
                                class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(novoIntervalo);
        }
        
        function removerIntervalo(btn) {
            if (confirm('Deseja remover este intervalo?')) {
                btn.closest('.intervalo-item').remove();
            }
        }
    </script>
    
</body>
</html>
