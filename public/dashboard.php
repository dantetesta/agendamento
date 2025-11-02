<?php
/**
 * Dashboard Principal - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Agendamento.php';
require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Models/Tag.php';

// Requer autenticação
Auth::requireAuth();

$user = Auth::user();
$agendamentoModel = new Agendamento();
$clienteModel = new Cliente();
$tagModel = new Tag();

// Busca próximos agendamentos
$proximosAgendamentos = $agendamentoModel->getProximos(Auth::id(), 5);

// Estatísticas
$totalAgendamentos = count($agendamentoModel->getByProfessor(Auth::id()));
$agendamentosHoje = count($agendamentoModel->getByPeriodo(Auth::id(), date('Y-m-d'), date('Y-m-d')));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Agenda do Professor Inteligente</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/pt-br.global.min.js"></script>
    
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .sidebar.hidden {
                transform: translateX(-100%);
            }
        }
        
        .fc-event {
            cursor: pointer;
        }
        
        /* Estilo customizado para visualização Agenda (Timeline do Dia) */
        .fc-list-day-cushion {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            color: white !important;
            font-weight: 600 !important;
            padding: 12px 16px !important;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .fc-list-event {
            border-left: 4px solid #3b82f6 !important;
            margin-bottom: 8px !important;
            border-radius: 6px !important;
            transition: all 0.2s !important;
        }
        
        .fc-list-event:hover {
            background: #f0f9ff !important;
            transform: translateX(4px) !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2) !important;
        }
        
        .fc-list-event-time {
            background: #eff6ff !important;
            color: #1e40af !important;
            font-weight: 600 !important;
            padding: 8px 12px !important;
            border-radius: 6px !important;
            min-width: 80px !important;
            text-align: center !important;
        }
        
        .fc-list-event-title {
            padding: 12px !important;
            font-weight: 500 !important;
        }
        
        .fc-list-empty {
            background: #f9fafb !important;
            padding: 40px !important;
            text-align: center !important;
            border-radius: 8px !important;
            color: #6b7280 !important;
        }
        
        .fc-list-empty::before {
            content: '📅';
            display: block;
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        /* Remove o calendário padrão do FullCalendar na mensagem vazia */
        .fc-list-empty-cushion {
            display: none !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Layout Principal -->
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
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-home mr-2 text-blue-600"></i>
                            <span class="xs:hidden">Início</span>
                        </h2>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="window.location.href='/clientes/novo'" 
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg transition flex items-center shadow-md hover:shadow-lg">
                            <i class="fas fa-user-plus"></i>
                            <span class="ml-2 hidden md:inline">Novo Cliente</span>
                        </button>
                        <button onclick="window.location.href='/agendamentos?novo=1'" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition flex items-center shadow-md hover:shadow-lg">
                            <i class="fas fa-calendar-plus"></i>
                            <span class="ml-2 hidden sm:inline">Novo Agendamento</span>
                            <span class="ml-2 sm:hidden">Novo</span>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Conteúdo -->
            <div class="p-6 space-y-6">
                
                <!-- Layout: 75% Calendário / 25% Sidebar -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    
                    <!-- Calendário (75%) -->
                    <div class="lg:col-span-3 bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                            Calendário
                        </h3>
                        <div id="calendar"></div>
                    </div>
                    
                    <!-- Sidebar Direita (25%) -->
                    <div class="space-y-4">
                        
                        <!-- Indicadores -->
                        <div class="space-y-3">
                            <!-- Total de Agendamentos -->
                            <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">Total de Agendamentos</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= $totalAgendamentos ?></p>
                                    </div>
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-calendar-check text-blue-600"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Agendamentos Hoje -->
                            <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-green-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">Agendamentos Hoje</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= $agendamentosHoje ?></p>
                                    </div>
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-clock text-green-600"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Próximos -->
                            <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-purple-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">Próximos</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= count($proximosAgendamentos) ?></p>
                                    </div>
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-arrow-right text-purple-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de Próximos Agendamentos -->
                        <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-list mr-1 text-blue-600 text-xs"></i>
                            Próximos
                        </h3>
                        
                        <?php if (empty($proximosAgendamentos)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-3"></i>
                                <p>Nenhum agendamento próximo</p>
                                <a href="/agendamentos/novo" class="text-blue-600 hover:underline text-sm mt-2 inline-block">
                                    Criar novo agendamento
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($proximosAgendamentos as $agendamento): ?>
                                    <?php 
                                    // ===== TAG DO CLIENTE (QUEM é) =====
                                    $tagCliente = null;
                                    $corBorda = '#3B82F6'; // Azul padrão
                                    $corFundo = '#EFF6FF'; // Azul claro padrão
                                    $clienteIdParaTags = null;
                                    
                                    // 1º: Tenta usar cliente_id diretamente
                                    if (!empty($agendamento['cliente_id'])) {
                                        $clienteIdParaTags = $agendamento['cliente_id'];
                                    } 
                                    // 2º: Se não tiver cliente_id, busca pelo nome
                                    else if (!empty($agendamento['aluno'])) {
                                        $clientePorNome = $clienteModel->getByNome($agendamento['aluno'], Auth::id());
                                        if (!empty($clientePorNome)) {
                                            $clienteIdParaTags = $clientePorNome['id'];
                                        }
                                    }
                                    
                                    // Busca tag do cliente
                                    if ($clienteIdParaTags) {
                                        $tagsCliente = $tagModel->getByCliente($clienteIdParaTags);
                                        if (!empty($tagsCliente)) {
                                            $tagCliente = $tagsCliente[0];
                                            $corBorda = $tagCliente['cor'];
                                            $corFundo = $corBorda . '10';
                                        }
                                    }
                                    
                                    // ===== TAG DO SERVIÇO (O QUÊ fazer) =====
                                    $tagServico = null;
                                    if (!empty($agendamento['tag_servico_id'])) {
                                        $tagServico = $tagModel->getByIdSimples($agendamento['tag_servico_id']);
                                    }
                                    ?>
                                    <div class="border-l-4 p-4 rounded" 
                                         style="border-color: <?= $corBorda ?>; background-color: <?= $corFundo ?>;">
                                        <div class="flex items-start gap-3">
                                            <!-- Foto do Cliente -->
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 border-2 border-white shadow-sm flex items-center justify-center">
                                                    <?php 
                                                    // Busca foto do cliente
                                                    $fotoCliente = null;
                                                    if ($clienteIdParaTags) {
                                                        $clienteCompleto = $clienteModel->findById($clienteIdParaTags);
                                                        if ($clienteCompleto) {
                                                            $fotoCliente = $clienteCompleto['foto'] ?? null;
                                                        }
                                                    }
                                                    
                                                    if ($fotoCliente): ?>
                                                        <img src="<?= $fotoCliente ?>" class="w-full h-full object-cover" alt="<?= sanitize($agendamento['aluno']) ?>">
                                                    <?php else: ?>
                                                        <span class="text-gray-400 font-bold text-lg">
                                                            <?= strtoupper(substr($agendamento['aluno'], 0, 1)) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Dados do Agendamento -->
                                            <div class="flex-1">
                                                <!-- Nome do cliente -->
                                                <p class="font-semibold text-gray-800 mb-1"><?= sanitize($agendamento['aluno']) ?></p>
                                                
                                                <!-- Tags embaixo (mais delicadas) -->
                                                <?php if ($tagCliente || $tagServico): ?>
                                                    <div class="flex items-center gap-1.5 mb-2">
                                                        <?php if ($tagCliente): ?>
                                                            <!-- Tag do Cliente (tipo de cliente) -->
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" 
                                                                  style="background-color: <?= $tagCliente['cor'] ?>20; color: <?= $tagCliente['cor'] ?>; border: 1px solid <?= $tagCliente['cor'] ?>40; font-size: 10px;">
                                                                <i class="fas <?= $tagCliente['icone'] ?> mr-1" style="font-size: 9px;"></i>
                                                                <?= sanitize($tagCliente['nome']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($tagServico): ?>
                                                            <!-- Tag do Serviço (tipo de atendimento) -->
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" 
                                                                  style="background-color: <?= $tagServico['cor'] ?>20; color: <?= $tagServico['cor'] ?>; border: 1px solid <?= $tagServico['cor'] ?>40; font-size: 10px;">
                                                                <i class="fas <?= $tagServico['icone'] ?> mr-1" style="font-size: 9px;"></i>
                                                                <?= sanitize($tagServico['nome']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex items-center gap-2 mt-1 text-xs text-gray-600">
                                                    <span class="flex items-center">
                                                        <i class="fas fa-calendar mr-1 text-xs"></i>
                                                        <?= formatDate($agendamento['data']) ?>
                                                    </span>
                                                    <span class="flex items-center">
                                                        <i class="fas fa-clock mr-1 text-xs"></i>
                                                        <?= substr($agendamento['hora_inicio'], 0, 5) ?> - <?= substr($agendamento['hora_fim'], 0, 5) ?>
                                                    </span>
                                                </div>
                                                <?php if ($agendamento['descricao']): ?>
                                                    <p class="text-xs text-gray-500 mt-2"><?= sanitize($agendamento['descricao']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <a href="/agendamentos" 
                               class="block text-center mt-4 text-blue-600 hover:text-blue-700 font-medium">
                                Ver todos os agendamentos →
                            </a>
                        <?php endif; ?>
                    </div>
                    
                </div>
                
            </div>
            
        </main>
        
    </div>
    
    <!-- Modal Detalhes do Agendamento -->
    <div id="modalDetalhes" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full transform transition-all">
            
            <!-- Header do Modal -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-calendar-check mr-3"></i>
                        Detalhes do Agendamento
                    </h3>
                    <button onclick="fecharModalDetalhes()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Corpo do Modal -->
            <div class="p-6 space-y-4">
                
                <!-- Cliente -->
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden" id="foto_cliente_container">
                        <i class="fas fa-user text-blue-600" id="icone_cliente"></i>
                        <img src="" id="foto_cliente" class="w-full h-full object-cover hidden" alt="Foto do cliente">
                    </div>
                    <div class="flex-1">
                        <div id="detalhe_tag_cliente" class="hidden"></div>
                        <p id="detalhe_aluno" class="text-lg font-semibold text-gray-800" style="margin-top: 2px;"></p>
                    </div>
                </div>
                
                <!-- Data -->
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Data</p>
                        <p id="detalhe_data" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                </div>
                
                <!-- Horário -->
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-purple-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Horário</p>
                        <p id="detalhe_horario" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                </div>
                
                <!-- Duração -->
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-orange-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Duração</p>
                        <p id="detalhe_duracao" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                </div>
                
                <!-- Tag de Serviço -->
                <div id="container_tag_servico" class="hidden">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-briefcase text-pink-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Tipo de Serviço</p>
                            <div id="detalhe_tag_servico" class="mt-1"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Descrição -->
                <div id="container_descricao" class="hidden">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-comment text-gray-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Observações</p>
                            <p id="detalhe_descricao" class="text-sm text-gray-700 mt-1 leading-relaxed"></p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Footer do Modal -->
            <div class="bg-gray-50 px-6 py-4 rounded-b-xl flex justify-between items-center">
                <a id="link_editar" href="#" class="text-blue-600 hover:text-blue-700 font-medium transition">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Agendamento
                </a>
                <button onclick="fecharModalDetalhes()" 
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Fechar
                </button>
            </div>
            
        </div>
    </div>
    
    <!-- Scripts -->
    <script>
        // Toggle Sidebar Mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
        
        /**
         * Abre modal com detalhes do agendamento
         */
        function abrirModalDetalhes(event) {
            console.log('📝 Abrindo detalhes do evento:', event);
            
            // Extrai dados do evento
            const aluno = event.title;
            const inicio = event.start;
            const fim = event.end;
            const descricao = event.extendedProps.descricao || '';
            const id = event.id;
            
            // Formata data
            const dataFormatada = inicio.toLocaleDateString('pt-BR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Formata horário
            const horaInicio = inicio.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const horaFim = fim ? fim.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            }) : horaInicio;
            
            // Calcula duração
            let duracao = '';
            if (fim) {
                const diffMs = fim - inicio;
                const diffMins = Math.round(diffMs / 60000);
                const horas = Math.floor(diffMins / 60);
                const minutos = diffMins % 60;
                
                if (horas > 0) {
                    duracao = `${horas}h`;
                    if (minutos > 0) duracao += ` ${minutos}min`;
                } else {
                    duracao = `${minutos} minutos`;
                }
            }
            
            // Preenche modal
            document.getElementById('detalhe_aluno').textContent = aluno;
            document.getElementById('detalhe_data').textContent = dataFormatada.charAt(0).toUpperCase() + dataFormatada.slice(1);
            document.getElementById('detalhe_horario').textContent = `${horaInicio} - ${horaFim}`;
            document.getElementById('detalhe_duracao').textContent = duracao || 'N/A';
            
            // ===== FOTO DO CLIENTE =====
            const fotoCliente = event.extendedProps?.fotoCliente;
            const imgFoto = document.getElementById('foto_cliente');
            const iconeFoto = document.getElementById('icone_cliente');
            
            if (fotoCliente) {
                imgFoto.src = fotoCliente;
                imgFoto.classList.remove('hidden');
                iconeFoto.classList.add('hidden');
            } else {
                imgFoto.classList.add('hidden');
                iconeFoto.classList.remove('hidden');
            }
            
            // ===== TAG DO CLIENTE (acima do nome) =====
            const tagCliente = event.extendedProps?.tagCliente;
            const containerTagCliente = document.getElementById('detalhe_tag_cliente');
            
            if (tagCliente) {
                containerTagCliente.innerHTML = `
                    <span class="inline-flex items-center text-xs font-medium" 
                          style="color: ${tagCliente.cor};">
                        <i class="fas ${tagCliente.icone}" style="font-size: 10px; margin-right: 4px;"></i>
                        ${tagCliente.nome}
                    </span>
                `;
                containerTagCliente.classList.remove('hidden');
            } else {
                containerTagCliente.classList.add('hidden');
            }
            
            // ===== TAG DE SERVIÇO =====
            const tagServico = event.extendedProps?.tagServico;
            const containerTagServico = document.getElementById('container_tag_servico');
            const detalheTagServico = document.getElementById('detalhe_tag_servico');
            
            if (tagServico) {
                detalheTagServico.innerHTML = `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" 
                          style="background-color: ${tagServico.cor}20; color: ${tagServico.cor}; border: 1px solid ${tagServico.cor}40;">
                        <i class="fas ${tagServico.icone} mr-2" style="font-size: 12px;"></i>
                        ${tagServico.nome}
                    </span>
                `;
                containerTagServico.classList.remove('hidden');
            } else {
                containerTagServico.classList.add('hidden');
            }
            
            // Descrição (opcional)
            const containerDescricao = document.getElementById('container_descricao');
            if (descricao && descricao.trim() !== '') {
                document.getElementById('detalhe_descricao').textContent = descricao;
                containerDescricao.classList.remove('hidden');
            } else {
                containerDescricao.classList.add('hidden');
            }
            
            // Link de editar - abre modal na página de agendamentos
            document.getElementById('link_editar').href = `/agendamentos?editar=${id}`;
            
            // Abre modal
            const modal = document.getElementById('modalDetalhes');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        /**
         * Fecha modal de detalhes
         */
        function fecharModalDetalhes() {
            const modal = document.getElementById('modalDetalhes');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Fecha modal ao clicar fora
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('modalDetalhes');
            if (event.target === modal) {
                fecharModalDetalhes();
            }
        });
        
        // Fecha modal com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                fecharModalDetalhes();
            }
        });
        
        // FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            // Recupera visualização salva no localStorage (padrão: dayGridMonth)
            const savedView = localStorage.getItem('calendarView') || 'dayGridMonth';
            console.log('📅 Visualização salva:', savedView);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: savedView,
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listDay'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia',
                    list: 'Agenda'
                },
                noEventsContent: function() {
                    return {
                        html: `
                            <div style="
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                justify-content: center;
                                padding: 60px 20px;
                                text-align: center;
                            ">
                                <div style="
                                    width: 80px;
                                    height: 80px;
                                    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin-bottom: 20px;
                                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
                                ">
                                    <i class="fas fa-calendar-day" style="
                                        font-size: 36px;
                                        color: #3b82f6;
                                    "></i>
                                </div>
                                <h3 style="
                                    font-size: 20px;
                                    font-weight: 600;
                                    color: #1f2937;
                                    margin: 0 0 8px 0;
                                ">
                                    Nenhum agendamento
                                </h3>
                                <p style="
                                    font-size: 14px;
                                    color: #6b7280;
                                    margin: 0;
                                    max-width: 300px;
                                ">
                                    Não há agendamentos para este dia. Clique em "Novo Agendamento" para adicionar.
                                </p>
                            </div>
                        `
                    };
                },
                views: {
                    listDay: {
                        type: 'listDay',
                        buttonText: 'Agenda',
                        listDayFormat: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
                    }
                },
                events: function(info, successCallback, failureCallback) {
                    console.log('🔍 Buscando eventos...', info);
                    
                    fetch('/api/eventos?start=' + info.startStr + '&end=' + info.endStr)
                        .then(response => {
                            console.log('📡 Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('✅ Eventos recebidos:', data);
                            successCallback(data);
                        })
                        .catch(error => {
                            console.error('❌ Erro ao buscar eventos:', error);
                            failureCallback(error);
                        });
                },
                eventClick: function(info) {
                    abrirModalDetalhes(info.event);
                },
                viewDidMount: function(info) {
                    // Salva a visualização atual no localStorage quando muda
                    const currentView = info.view.type;
                    localStorage.setItem('calendarView', currentView);
                    console.log('💾 Visualização salva:', currentView);
                },
                eventContent: function(arg) {
                    const corCliente = arg.event.backgroundColor || '#3B82F6';
                    const tagServico = arg.event.extendedProps?.tagServico;
                    const horario = arg.timeText;
                    const titulo = arg.event.title;
                    const view = arg.view.type;
                    
                    // Debug
                    console.log('🎨 Evento:', titulo, 'Cor Cliente:', corCliente, 'Tag Serviço:', tagServico?.nome);
                    
                    // Vista de mês: bolinha do cliente + badge do serviço
                    if (view === 'dayGridMonth') {
                        let badgeServico = '';
                        if (tagServico) {
                            badgeServico = `
                                <span style="
                                    background-color: ${tagServico.cor}40;
                                    color: ${tagServico.cor};
                                    padding: 1px 4px;
                                    border-radius: 3px;
                                    font-size: 9px;
                                    font-weight: 600;
                                    margin-left: 2px;
                                ">
                                    <i class="fas ${tagServico.icone}" style="font-size: 8px;"></i>
                                </span>
                            `;
                        }
                        
                        return {
                            html: `
                                <div style="display: flex; align-items: center; gap: 4px; padding: 2px 4px; overflow: hidden;">
                                    <span style="
                                        width: 8px; 
                                        height: 8px; 
                                        border-radius: 50%; 
                                        background-color: ${corCliente};
                                        flex-shrink: 0;
                                    "></span>
                                    <span style="
                                        font-size: 12px;
                                        white-space: nowrap;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                        color: #1f2937;
                                    ">${horario} ${titulo}${badgeServico}</span>
                                </div>
                            `
                        };
                    }
                    
                    // Vista de semana/dia: fundo do cliente + badge do serviço
                    let badgeServicoSemana = '';
                    if (tagServico) {
                        badgeServicoSemana = `
                            <div style="
                                background-color: ${tagServico.cor};
                                padding: 2px 6px;
                                border-radius: 3px;
                                font-size: 10px;
                                margin-top: 3px;
                                display: inline-block;
                            ">
                                <i class="fas ${tagServico.icone}" style="font-size: 9px;"></i>
                                ${tagServico.nome}
                            </div>
                        `;
                    }
                    
                    return {
                        html: `
                            <div style="
                                background-color: ${corCliente};
                                color: white;
                                padding: 4px 6px;
                                border-radius: 4px;
                                height: 100%;
                                overflow: hidden;
                                font-weight: 500;
                            ">
                                <div style="font-size: 11px; opacity: 0.9; margin-bottom: 2px;">${horario}</div>
                                <div style="font-size: 13px; font-weight: 600;">${titulo}</div>
                                ${badgeServicoSemana}
                            </div>
                        `
                    };
                },
                height: 'auto',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                eventDidMount: function(info) {
                    console.log('📅 Evento renderizado:', info.event.title);
                }
            });
            
            calendar.render();
        });
        
        /**
         * Abre modal de agendamento
         * Redireciona para página de agendamentos com parâmetro para abrir modal
         */
        // Função removida - botão já redireciona direto
    </script>
    
</body>
</html>
