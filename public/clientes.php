<?php
/**
 * Página de Clientes - Agenda Professor
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 17:52
 */

// Debug temporário
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

// Verifica autenticação
Auth::requireAuth();

// Define variável $user para o sidebar
$user = Auth::user();

// Verifica se tabela clientes existe
try {
    require_once __DIR__ . '/../app/Models/Cliente.php';
    require_once __DIR__ . '/../app/Models/Tag.php';
} catch (Exception $e) {
    die('<h1>⚠️ Tabela de Clientes não encontrada</h1>
         <p>Execute a migração primeiro:</p>
         <p><a href="/create_clientes_table.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Criar Tabela de Clientes</a></p>
         <p style="color: red;">Erro: ' . $e->getMessage() . '</p>');
}

$clienteModel = new Cliente();
$tagModel = new Tag();
$professorId = Auth::id();

// Processamento de ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Deletar cliente
    if (isset($_POST['deletar'])) {
        $id = $_POST['id'] ?? null;
        
        if ($id) {
            $cliente = $clienteModel->findByIdAndProfessor($id, $professorId);
            
            if ($cliente) {
                $clienteModel->delete($id);
                setFlash('success', 'Cliente inativado com sucesso!');
            } else {
                setFlash('error', 'Cliente não encontrado.');
            }
        }
        
        redirect('/clientes');
    }
}

// Filtros
$status = $_GET['status'] ?? 'ativo';
$busca = $_GET['busca'] ?? '';

// Busca clientes
$clientes = $clienteModel->findByProfessor($professorId, $status);

// Filtra por busca
if ($busca) {
    $clientes = array_filter($clientes, function($cliente) use ($busca) {
        return stripos($cliente['nome'], $busca) !== false ||
               stripos($cliente['email'], $busca) !== false ||
               stripos($cliente['telefone'], $busca) !== false;
    });
}

// Estatísticas
$totalAtivos = $clienteModel->countByProfessor($professorId, 'ativo');
$totalInativos = $clienteModel->countByProfessor($professorId, 'inativo');
$totalGeral = $totalAtivos + $totalInativos;

$currentPage = 'clientes.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Agenda Professor</title>
    
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
                        <button onclick="toggleSidebar()" class="md:hidden text-gray-600">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-users mr-2 text-blue-600"></i>
                            Clientes
                        </h2>
                    </div>
                    <a href="/clientes/novo" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition flex items-center shadow-md hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i>
                        <span class="hidden sm:inline">Novo Cliente</span>
                        <span class="sm:hidden">Novo</span>
                    </a>
                </div>
            </header>
            
            <!-- Conteúdo -->
            <div class="flex-1 overflow-auto p-6">
                
                <!-- Mensagens Flash -->
                <?php if ($success = flash('success')): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                            <p class="text-green-700"><?= sanitize($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error = flash('error')): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                            <p class="text-red-700"><?= sanitize($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Total de Clientes</p>
                                <p class="text-3xl font-bold text-gray-800"><?= $totalGeral ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Clientes Ativos</p>
                                <p class="text-3xl font-bold text-green-600"><?= $totalAtivos ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Clientes Inativos</p>
                                <p class="text-3xl font-bold text-gray-600"><?= $totalInativos ?></p>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-gray-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros e Busca -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <!-- Busca -->
                        <div class="flex-1">
                            <input type="text" 
                                   name="busca" 
                                   value="<?= sanitize($busca) ?>"
                                   placeholder="Buscar por nome, e-mail ou telefone..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent">
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <select name="status" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                    onchange="this.form.submit()">
                                <option value="">Todos Status</option>
                                <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                                <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativos</option>
                            </select>
                        </div>
                        
                        <!-- Tags -->
                        <div>
                            <select name="tag" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                    onchange="this.form.submit()">
                                <option value="">Todas Tags</option>
                                <?php 
                                $todasTagsFiltro = $tagModel->getAll();
                                foreach ($todasTagsFiltro as $tagFiltro): 
                                ?>
                                    <option value="<?= $tagFiltro['id'] ?>" <?= ($tagFiltro ?? '') == $tagFiltro['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($tagFiltro['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Botões -->
                        <div class="flex gap-2">
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                                <i class="fas fa-search mr-2"></i>
                                Buscar
                            </button>
                            <a href="/clientes" 
                               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition">
                                <i class="fas fa-times mr-2"></i>
                                Limpar
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Lista de Clientes -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($clientes)): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600 text-lg mb-4">Nenhum cliente encontrado</p>
                            <a href="/clientes/novo" 
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                                <i class="fas fa-plus mr-2"></i>
                                Cadastrar Primeiro Cliente
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cliente
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                            Contato
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                            Tags
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
                                                        <?php if (!empty($cliente['foto'])): ?>
                                                            <img src="<?= $cliente['foto'] ?>" class="w-full h-full object-cover" alt="<?= sanitize($cliente['nome']) ?>">
                                                        <?php else: ?>
                                                            <span class="text-blue-600 font-bold">
                                                                <?= strtoupper(substr($cliente['nome'], 0, 1)) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?= sanitize($cliente['nome']) ?>
                                                        </div>
                                                        <?php 
                                                        $totalAgendamentos = $clienteModel->countAgendamentos($cliente['id']);
                                                        if ($totalAgendamentos > 0):
                                                        ?>
                                                            <div class="text-xs text-gray-500">
                                                                <?= $totalAgendamentos ?> agendamento(s)
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                                <div class="text-sm text-gray-900">
                                                    <?= $cliente['email'] ? sanitize($cliente['email']) : '-' ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= $cliente['telefone'] ? sanitize($cliente['telefone']) : '-' ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php 
                                                    $tags = $tagModel->getByCliente($cliente['id']);
                                                    if (empty($tags)): 
                                                    ?>
                                                        <span class="text-xs text-gray-400">-</span>
                                                    <?php else: ?>
                                                        <?php foreach ($tags as $tag): ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" 
                                                                  style="background-color: <?= $tag['cor'] ?>20; color: <?= $tag['cor'] ?>; border: 1px solid <?= $tag['cor'] ?>40;">
                                                                <i class="fas <?= $tag['icone'] ?> mr-1"></i>
                                                                <?= sanitize($tag['nome']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($cliente['status'] === 'ativo'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Ativo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Inativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end gap-2">
                                                    <a href="/clientes/editar/<?= $cliente['id'] ?>" 
                                                       class="text-blue-600 hover:text-blue-900"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="/clientes/ver/<?= $cliente['id'] ?>" 
                                                       class="text-green-600 hover:text-green-900"
                                                       title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente inativar este cliente?')">
                                                        <input type="hidden" name="id" value="<?= $cliente['id'] ?>">
                                                        <button type="submit" 
                                                                name="deletar" 
                                                                class="text-red-600 hover:text-red-900"
                                                                title="Inativar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="/public/assets/js/sidebar.js"></script>
</body>
</html>
