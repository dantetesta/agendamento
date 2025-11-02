<?php
/**
 * Gerenciamento de Tags/Categorias de Clientes
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 31/10/2025 02:30
 */

// Habilita erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Tag.php';

Auth::requireAuth();
$user = Auth::user();
$tagModel = new Tag();

$errors = [];
$success = false;

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Criar tag
    if (isset($_POST['criar'])) {
        $nome = sanitize($_POST['nome'] ?? '');
        $cor = sanitize($_POST['cor'] ?? '#3B82F6');
        $icone = sanitize($_POST['icone'] ?? 'fa-tag');
        $descricao = sanitize($_POST['descricao'] ?? '');
        $categoria = sanitize($_POST['categoria'] ?? 'cliente');
        
        if (empty($nome)) {
            $errors[] = 'Nome da tag é obrigatório';
        } else {
            try {
                $tagModel->create([
                    'nome' => $nome,
                    'cor' => $cor,
                    'icone' => $icone,
                    'descricao' => $descricao,
                    'categoria' => $categoria
                ]);
                setFlash('success', 'Tag criada com sucesso!');
                redirect('/tags');
            } catch (Exception $e) {
                $errors[] = 'Erro ao criar tag: ' . $e->getMessage();
            }
        }
    }
    
    // Editar tag
    if (isset($_POST['editar'])) {
        $id = intval($_POST['id'] ?? 0);
        $nome = sanitize($_POST['nome'] ?? '');
        $cor = sanitize($_POST['cor'] ?? '#3B82F6');
        $icone = sanitize($_POST['icone'] ?? 'fa-tag');
        $descricao = sanitize($_POST['descricao'] ?? '');
        $categoria = sanitize($_POST['categoria'] ?? 'cliente');
        
        if (empty($nome)) {
            $errors[] = 'Nome da tag é obrigatório';
        } else {
            try {
                $tagModel->update($id, [
                    'nome' => $nome,
                    'cor' => $cor,
                    'icone' => $icone,
                    'descricao' => $descricao,
                    'categoria' => $categoria
                ]);
                setFlash('success', 'Tag atualizada com sucesso!');
                redirect('/tags');
            } catch (Exception $e) {
                $errors[] = 'Erro ao atualizar tag: ' . $e->getMessage();
            }
        }
    }
    
    // Deletar tag
    if (isset($_POST['deletar'])) {
        $id = intval($_POST['id'] ?? 0);
        try {
            $tagModel->delete($id);
            setFlash('success', 'Tag deletada com sucesso!');
            redirect('/tags');
        } catch (Exception $e) {
            $errors[] = 'Erro ao deletar tag: ' . $e->getMessage();
        }
    }
}

$tags = $tagModel->getStats();
$tagEditando = null;

// Se está editando
if (isset($_GET['editar'])) {
    $tagEditando = $tagModel->getById($_GET['editar']);
}

// Define currentPage para o sidebar
$currentPage = 'tags.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - Agenda Professor</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) {
            .sidebar.hidden { transform: translateX(-100%); }
        }
        
        /* Modal de ícones */
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 0.5rem;
        }
        
        .icon-item {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .icon-item:hover {
            border-color: #3B82F6;
            background-color: #EFF6FF;
            transform: scale(1.05);
        }
        
        .icon-item.selected {
            border-color: #3B82F6;
            background-color: #3B82F6;
            color: white;
        }
    </style>
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
                            <i class="fas fa-tags mr-2 text-blue-600"></i>
                            Gerenciar Tags
                        </h2>
                    </div>
                </div>
            </header>
            
            <!-- Conteúdo Scrollável -->
            <div class="flex-1 overflow-auto p-6">
            
            <!-- Mensagens -->
            <?php if ($msg = flash('success')): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-green-700"><?= $msg ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-red-700"><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Formulário -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold mb-4">
                            <?= $tagEditando ? 'Editar Tag' : 'Nova Tag' ?>
                        </h2>
                        
                        <form method="POST">
                            <?php if ($tagEditando): ?>
                                <input type="hidden" name="id" value="<?= $tagEditando['id'] ?>">
                            <?php endif; ?>
                            
                            <!-- Nome -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome da Tag *
                                </label>
                                <input type="text" 
                                       name="nome" 
                                       value="<?= $tagEditando['nome'] ?? '' ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600"
                                       placeholder="Ex: Consultoria"
                                       required>
                            </div>
                            
                            <!-- Cor -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Cor
                                </label>
                                <input type="color" 
                                       name="cor" 
                                       value="<?= $tagEditando['cor'] ?? '#3B82F6' ?>"
                                       class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer">
                            </div>
                            
                            <!-- Ícone -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ícone
                                </label>
                                <input type="hidden" 
                                       id="icone_input" 
                                       name="icone" 
                                       value="<?= $tagEditando['icone'] ?? 'fa-tag' ?>">
                                
                                <div class="flex gap-2">
                                    <div id="icon_preview" 
                                         class="w-16 h-16 border-2 border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                                        <i class="fas <?= $tagEditando['icone'] ?? 'fa-tag' ?> fa-2x text-gray-600"></i>
                                    </div>
                                    <button type="button" 
                                            onclick="openIconPicker()" 
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-left flex items-center justify-between">
                                        <span id="icon_name"><?= $tagEditando['icone'] ?? 'fa-tag' ?></span>
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Clique para selecionar um ícone
                                </p>
                            </div>
                            
                            <!-- Descrição -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descrição
                                </label>
                                <textarea name="descricao" 
                                          rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600"
                                          placeholder="Opcional"><?= $tagEditando['descricao'] ?? '' ?></textarea>
                            </div>
                            
                            <!-- Categoria -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Categoria *
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                        <input type="radio" 
                                               name="categoria" 
                                               value="cliente" 
                                               <?= (!isset($tagEditando['categoria']) || $tagEditando['categoria'] === 'cliente') ? 'checked' : '' ?>
                                               class="w-4 h-4 text-blue-600">
                                        <div>
                                            <span class="font-medium text-gray-800">Cliente</span>
                                            <p class="text-xs text-gray-500">Para classificar tipos de clientes (Aluno, Paciente, VIP, etc)</p>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                        <input type="radio" 
                                               name="categoria" 
                                               value="servico" 
                                               <?= (isset($tagEditando['categoria']) && $tagEditando['categoria'] === 'servico') ? 'checked' : '' ?>
                                               class="w-4 h-4 text-blue-600">
                                        <div>
                                            <span class="font-medium text-gray-800">Serviço</span>
                                            <p class="text-xs text-gray-500">Para classificar tipos de atendimentos (Mentoria, Consultoria, Aula, etc)</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Botões -->
                            <div class="flex gap-2">
                                <button type="submit" 
                                        name="<?= $tagEditando ? 'editar' : 'criar' ?>"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                                    <i class="fas fa-save mr-2"></i>
                                    <?= $tagEditando ? 'Atualizar' : 'Criar Tag' ?>
                                </button>
                                
                                <?php if ($tagEditando): ?>
                                    <a href="/tags" 
                                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                                        Cancelar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de Tags Organizadas -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <?php 
                    // Separa tags por categoria
                    $tagsCliente = array_filter($tags, function($tag) {
                        return ($tag['categoria'] ?? 'cliente') === 'cliente';
                    });
                    $tagsServico = array_filter($tags, function($tag) {
                        return ($tag['categoria'] ?? 'cliente') === 'servico';
                    });
                    ?>
                    
                    <!-- TAGS DE CLIENTE -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-blue-100">
                            <h2 class="text-lg font-bold text-blue-900 flex items-center gap-2">
                                <i class="fas fa-users"></i>
                                Tags de Cliente (<?= count($tagsCliente) ?>)
                            </h2>
                            <p class="text-xs text-blue-700 mt-1">Para classificar tipos de clientes</p>
                        </div>
                        
                        <div class="p-6">
                            <?php if (empty($tagsCliente)): ?>
                                <p class="text-gray-500 text-center py-8">
                                    <i class="fas fa-info-circle text-3xl mb-2"></i><br>
                                    Nenhuma tag de cliente cadastrada
                                </p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($tagsCliente as $tag): ?>
                                        <div class="border-2 border-blue-100 rounded-lg p-4 hover:shadow-md transition hover:border-blue-300">
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                         style="background-color: <?= $tag['cor'] ?>20;">
                                                        <i class="fas <?= $tag['icone'] ?>" style="color: <?= $tag['cor'] ?>"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-gray-900"><?= sanitize($tag['nome']) ?></h3>
                                                        <p class="text-xs text-gray-500">
                                                            <?= $tag['total_clientes'] ?> cliente(s)
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex gap-1">
                                                    <a href="/tags?editar=<?= $tag['id'] ?>" 
                                                       class="text-blue-600 hover:text-blue-800 p-2"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="inline" 
                                                          onsubmit="return confirm('Deletar esta tag?')">
                                                        <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                                        <button type="submit" 
                                                                name="deletar"
                                                                class="text-red-600 hover:text-red-800 p-2"
                                                                title="Deletar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <?php if ($tag['descricao']): ?>
                                                <p class="text-sm text-gray-600 mb-3"><?= sanitize($tag['descricao']) ?></p>
                                            <?php endif; ?>
                                            
                                            <!-- Preview da tag -->
                                            <div class="pt-3 border-t">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" 
                                                      style="background-color: <?= $tag['cor'] ?>20; color: <?= $tag['cor'] ?>; border: 1px solid <?= $tag['cor'] ?>40;">
                                                    <i class="fas <?= $tag['icone'] ?> mr-1"></i>
                                                    <?= sanitize($tag['nome']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- TAGS DE SERVIÇO -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b bg-gradient-to-r from-purple-50 to-purple-100">
                            <h2 class="text-lg font-bold text-purple-900 flex items-center gap-2">
                                <i class="fas fa-briefcase"></i>
                                Tags de Serviço (<?= count($tagsServico) ?>)
                            </h2>
                            <p class="text-xs text-purple-700 mt-1">Para classificar tipos de atendimentos</p>
                        </div>
                        
                        <div class="p-6">
                            <?php if (empty($tagsServico)): ?>
                                <p class="text-gray-500 text-center py-8">
                                    <i class="fas fa-info-circle text-3xl mb-2"></i><br>
                                    Nenhuma tag de serviço cadastrada
                                </p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($tagsServico as $tag): ?>
                                        <div class="border-2 border-purple-100 rounded-lg p-4 hover:shadow-md transition hover:border-purple-300">
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                         style="background-color: <?= $tag['cor'] ?>20;">
                                                        <i class="fas <?= $tag['icone'] ?>" style="color: <?= $tag['cor'] ?>"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-gray-900"><?= sanitize($tag['nome']) ?></h3>
                                                        <p class="text-xs text-gray-500">
                                                            <?= $tag['total_clientes'] ?> cliente(s)
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex gap-1">
                                                    <a href="/tags?editar=<?= $tag['id'] ?>" 
                                                       class="text-purple-600 hover:text-purple-800 p-2"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" class="inline" 
                                                          onsubmit="return confirm('Deletar esta tag?')">
                                                        <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                                        <button type="submit" 
                                                                name="deletar"
                                                                class="text-red-600 hover:text-red-800 p-2"
                                                                title="Deletar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <?php if ($tag['descricao']): ?>
                                                <p class="text-sm text-gray-600 mb-3"><?= sanitize($tag['descricao']) ?></p>
                                            <?php endif; ?>
                                            
                                            <!-- Preview da tag -->
                                            <div class="pt-3 border-t">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" 
                                                      style="background-color: <?= $tag['cor'] ?>20; color: <?= $tag['cor'] ?>; border: 1px solid <?= $tag['cor'] ?>40;">
                                                    <i class="fas <?= $tag['icone'] ?> mr-1"></i>
                                                    <?= sanitize($tag['nome']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
            </div>
            
            </div><!-- Fecha overflow-auto -->
        </div><!-- Fecha flex-1 -->
    </div><!-- Fecha flex screen -->
    
    <!-- Modal de Seleção de Ícones -->
    <div id="iconModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-icons mr-2 text-blue-600"></i>
                    Selecione um Ícone
                </h3>
                <button onclick="closeIconPicker()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Busca -->
            <div class="p-4 border-b">
                <input type="text" 
                       id="icon_search" 
                       placeholder="Buscar ícone..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600"
                       oninput="filterIcons()">
            </div>
            
            <!-- Grid de Ícones -->
            <div class="flex-1 overflow-auto p-6">
                <div id="icon_grid" class="icon-grid"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
        
        // ===== ICON PICKER =====
        
        // Lista de ícones populares do FontAwesome 6
        const icons = [
            'fa-tag', 'fa-tags', 'fa-star', 'fa-heart', 'fa-user', 'fa-users',
            'fa-briefcase', 'fa-graduation-cap', 'fa-crown', 'fa-building',
            'fa-headset', 'fa-phone', 'fa-envelope', 'fa-calendar', 'fa-clock',
            'fa-home', 'fa-shopping-cart', 'fa-credit-card', 'fa-dollar-sign',
            'fa-chart-line', 'fa-chart-pie', 'fa-chart-bar', 'fa-trophy',
            'fa-medal', 'fa-award', 'fa-gift', 'fa-rocket', 'fa-fire',
            'fa-bolt', 'fa-sparkles', 'fa-wand-magic', 'fa-gem', 'fa-diamond',
            'fa-music', 'fa-video', 'fa-camera', 'fa-image', 'fa-file',
            'fa-folder', 'fa-bookmark', 'fa-book', 'fa-newspaper', 'fa-pen',
            'fa-pencil', 'fa-paintbrush', 'fa-palette', 'fa-code', 'fa-laptop',
            'fa-mobile', 'fa-tablet', 'fa-desktop', 'fa-keyboard', 'fa-mouse',
            'fa-gamepad', 'fa-puzzle-piece', 'fa-dice', 'fa-chess', 'fa-cards',
            'fa-basketball', 'fa-football', 'fa-baseball', 'fa-volleyball',
            'fa-dumbbell', 'fa-running', 'fa-bicycle', 'fa-car', 'fa-plane',
            'fa-ship', 'fa-train', 'fa-bus', 'fa-taxi', 'fa-truck',
            'fa-map', 'fa-map-pin', 'fa-compass', 'fa-globe', 'fa-earth',
            'fa-coffee', 'fa-mug-hot', 'fa-pizza', 'fa-hamburger', 'fa-cake',
            'fa-ice-cream', 'fa-apple', 'fa-lemon', 'fa-leaf', 'fa-tree',
            'fa-cloud', 'fa-sun', 'fa-moon', 'fa-snowflake', 'fa-umbrella',
            'fa-lightbulb', 'fa-bell', 'fa-flag', 'fa-shield', 'fa-lock',
            'fa-key', 'fa-wrench', 'fa-screwdriver', 'fa-hammer', 'fa-tools',
            'fa-cog', 'fa-sliders', 'fa-power-off', 'fa-plug', 'fa-battery',
            'fa-wifi', 'fa-bluetooth', 'fa-rss', 'fa-print', 'fa-fax',
            'fa-microphone', 'fa-headphones', 'fa-volume-high', 'fa-signal',
            'fa-thumbs-up', 'fa-thumbs-down', 'fa-hand', 'fa-handshake',
            'fa-comments', 'fa-comment', 'fa-message', 'fa-inbox', 'fa-paper-plane',
            'fa-at', 'fa-link', 'fa-share', 'fa-retweet', 'fa-download',
            'fa-upload', 'fa-cloud-arrow-up', 'fa-cloud-arrow-down',
            'fa-circle-check', 'fa-circle-xmark', 'fa-circle-plus', 'fa-circle-minus',
            'fa-circle-info', 'fa-circle-question', 'fa-triangle-exclamation',
            'fa-ban', 'fa-trash', 'fa-edit', 'fa-save', 'fa-copy',
            'fa-paste', 'fa-cut', 'fa-magnifying-glass', 'fa-eye', 'fa-eye-slash'
        ];
        
        let allIcons = [...icons];
        let selectedIcon = document.getElementById('icone_input').value || 'fa-tag';
        
        // Abre o modal
        function openIconPicker() {
            renderIcons(allIcons);
            document.getElementById('iconModal').classList.remove('hidden');
        }
        
        // Fecha o modal
        function closeIconPicker() {
            document.getElementById('iconModal').classList.add('hidden');
            document.getElementById('icon_search').value = '';
        }
        
        // Renderiza os ícones
        function renderIcons(iconsToRender) {
            const grid = document.getElementById('icon_grid');
            grid.innerHTML = '';
            
            iconsToRender.forEach(icon => {
                const div = document.createElement('div');
                div.className = 'icon-item' + (icon === selectedIcon ? ' selected' : '');
                div.innerHTML = `<i class="fas ${icon} fa-2x"></i>`;
                div.onclick = () => selectIcon(icon);
                grid.appendChild(div);
            });
        }
        
        // Seleciona um ícone
        function selectIcon(icon) {
            selectedIcon = icon;
            
            // Atualiza input hidden
            document.getElementById('icone_input').value = icon;
            
            // Atualiza preview
            document.getElementById('icon_preview').innerHTML = `<i class="fas ${icon} fa-2x text-gray-600"></i>`;
            
            // Atualiza nome
            document.getElementById('icon_name').textContent = icon;
            
            // Fecha modal
            closeIconPicker();
        }
        
        // Filtra ícones
        function filterIcons() {
            const search = document.getElementById('icon_search').value.toLowerCase();
            const filtered = allIcons.filter(icon => icon.includes(search));
            renderIcons(filtered);
        }
        
        // Fecha modal ao clicar fora
        document.getElementById('iconModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeIconPicker();
            }
        });
    </script>
    
</body>
</html>
