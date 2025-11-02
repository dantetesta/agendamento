<?php
/**
 * Detalhes do Cliente - Agenda Professor
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 18:27
 */

// Debug temporário
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Models/Tag.php';

// Verifica autenticação
Auth::requireAuth();

// Define variável $user para o sidebar
$user = Auth::user();

$clienteModel = new Cliente();
$tagModel = new Tag();
$professorId = Auth::id();

// Pega ID do cliente
$id = $_GET['id'] ?? null;

if (!$id) {
    setFlash('error', 'Cliente não encontrado.');
    redirect('/clientes');
}

// Busca cliente
$cliente = $clienteModel->findByIdAndProfessor($id, $professorId);

if (!$cliente) {
    setFlash('error', 'Cliente não encontrado.');
    redirect('/clientes');
}

// Busca agendamentos do cliente
$agendamentos = $clienteModel->getAgendamentos($id);

// Separa agendamentos por status temporal
$hoje = date('Y-m-d');
$agora = date('Y-m-d H:i:s');

$futuros = [];
$passados = [];

foreach ($agendamentos as $agendamento) {
    $dataHora = $agendamento['data'] . ' ' . $agendamento['hora_inicio'];
    
    if ($dataHora >= $agora) {
        $futuros[] = $agendamento;
    } else {
        $passados[] = $agendamento;
    }
}

// Estatísticas
$totalAgendamentos = count($agendamentos);
$totalFuturos = count($futuros);
$totalPassados = count($passados);

$currentPage = 'clientes.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Cliente - Agenda Professor</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php include __DIR__ . '/../app/Views/partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <a href="/clientes" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left text-xl"></i>
                        </a>
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-user mr-2 text-blue-600"></i>
                            Detalhes do Cliente
                        </h2>
                    </div>
                    <a href="/clientes/editar/<?= $cliente['id'] ?>" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>
                        Editar
                    </a>
                </div>
            </header>
            
            <!-- Conteúdo -->
            <div class="flex-1 overflow-auto p-6">
                
                <!-- Card do Cliente - Compacto -->
                <div class="bg-white rounded-lg shadow p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <!-- Avatar e Info -->
                        <div class="flex items-center space-x-3">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-2xl font-bold text-white shadow-lg overflow-hidden">
                                <?php if (!empty($cliente['foto'])): ?>
                                    <img src="<?= $cliente['foto'] ?>" class="w-full h-full object-cover" alt="<?= sanitize($cliente['nome']) ?>">
                                <?php else: ?>
                                    <?= strtoupper(substr($cliente['nome'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900"><?= sanitize($cliente['nome']) ?></h3>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <?php if ($cliente['status'] === 'ativo'): ?>
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded">✅ Ativo</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded">⏸️ Inativo</span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Busca tags do cliente
                                    $tags = $tagModel->getByCliente($cliente['id']);
                                    if (!empty($tags)): 
                                    ?>
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" 
                                                  style="background-color: <?= $tag['cor'] ?>20; color: <?= $tag['cor'] ?>; border: 1px solid <?= $tag['cor'] ?>40;">
                                                <i class="fas <?= $tag['icone'] ?> mr-1" style="font-size: 10px;"></i>
                                                <?= sanitize($tag['nome']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <span class="text-xs text-gray-500">Cliente desde <?= formatDate($cliente['criado_em']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações de Contato - Grid Compacto -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4 pt-4 border-t">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-phone text-blue-600 text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-500">Telefone</p>
                                <p class="font-medium text-gray-900 text-sm truncate">
                                    <?= $cliente['telefone'] ? sanitize($cliente['telefone']) : 'Não informado' ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-envelope text-purple-600 text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-500">E-mail</p>
                                <p class="font-medium text-gray-900 text-sm truncate">
                                    <?= $cliente['email'] ? sanitize($cliente['email']) : 'Não informado' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observações -->
                    <?php if ($cliente['observacoes']): ?>
                    <div class="bg-gray-50 rounded-lg p-3 mt-3">
                        <p class="text-xs font-semibold text-gray-700 mb-1 flex items-center gap-1">
                            <i class="fas fa-sticky-note text-gray-500"></i>
                            Observações
                        </p>
                        <p class="text-sm text-gray-600"><?= nl2br(sanitize($cliente['observacoes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Estatísticas - Compactas -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-calendar text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Total</p>
                                <p class="text-2xl font-bold text-gray-800"><?= $totalAgendamentos ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-arrow-right text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Próximos</p>
                                <p class="text-2xl font-bold text-green-600"><?= $totalFuturos ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check text-gray-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Realizados</p>
                                <p class="text-2xl font-bold text-gray-600"><?= $totalPassados ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Agendamentos Futuros -->
                <?php if (!empty($futuros)): ?>
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-bold text-gray-900">
                            <i class="fas fa-calendar-check mr-2 text-green-600"></i>
                            Próximos Agendamentos (<?= $totalFuturos ?>)
                        </h3>
                    </div>
                    <div class="divide-y">
                        <?php foreach ($futuros as $agendamento): ?>
                        <div class="px-6 py-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-calendar text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= sanitize($agendamento['titulo'] ?? $agendamento['aluno'] ?? 'Sem título') ?></p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= $agendamento['data_formatada'] ?? '' ?> às <?= $agendamento['horario_formatado'] ?? '' ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                                    Futuro
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Agendamentos Passados -->
                <?php if (!empty($passados)): ?>
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-bold text-gray-900">
                            <i class="fas fa-history mr-2 text-gray-600"></i>
                            Histórico de Agendamentos (<?= $totalPassados ?>)
                        </h3>
                    </div>
                    <div class="divide-y">
                        <?php foreach ($passados as $agendamento): ?>
                        <div class="px-6 py-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-check text-gray-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= sanitize($agendamento['titulo'] ?? $agendamento['aluno'] ?? 'Sem título') ?></p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= $agendamento['data_formatada'] ?? '' ?> às <?= $agendamento['horario_formatado'] ?? '' ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-semibold rounded-full">
                                    Realizado
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sem Agendamentos -->
                <?php if (empty($agendamentos)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 text-lg mb-4">Nenhum agendamento encontrado</p>
                    <a href="/agendamentos?novo=1&cliente_id=<?= $cliente['id'] ?>" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>
                        Criar Primeiro Agendamento
                    </a>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <script src="/public/assets/js/sidebar.js"></script>
</body>
</html>
