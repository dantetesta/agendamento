<?php
/**
 * Sidebar Component - Reutilizável
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

$currentPage = basename($_SERVER['PHP_SELF']);

// Carrega configurações do app
$appConfig = require __DIR__ . '/../../../config/app.php';
$appName = $appConfig['name'] ?? 'Agenda Master';
$appIcon = $appConfig['logo']['icon'] ?? 'fas fa-calendar-check';
?>

<aside id="sidebar" class="sidebar fixed md:static inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white flex flex-col shadow-xl">
    
    <!-- Header Sidebar -->
    <div class="p-6 border-b border-blue-700">
        <div class="flex items-center justify-between mb-4">
            <a href="/dashboard" class="text-xl font-bold hover:text-blue-200 transition-colors">
                <i class="<?= $appIcon ?> mr-2"></i>
                <?= $appName ?>
            </a>
            <button onclick="toggleSidebar()" class="md:hidden text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Perfil -->
        <div class="flex items-center space-x-3">
            <?php if ($user['foto']): ?>
                <img src="<?= upload('users/' . sanitize($user['foto'])) ?>" 
                     alt="Foto" 
                     class="w-12 h-12 rounded-full object-cover border-2 border-blue-400">
            <?php else: ?>
                <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-xl font-bold">
                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="font-semibold truncate"><?= sanitize($user['nome']) ?></p>
                <p class="text-xs text-blue-200 truncate"><?= sanitize($user['email']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Menu -->
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        
        <!-- Visão Geral -->
        <a href="/dashboard" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= $currentPage === 'dashboard.php' ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
            <i class="fas fa-home w-5"></i>
            <span>Visão Geral</span>
        </a>
        
        <!-- Agendamentos (Criar/Listar) -->
        <a href="/agendamentos" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= $currentPage === 'agendamentos.php' ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
            <i class="fas fa-calendar-plus w-5"></i>
            <span>Agendamentos</span>
        </a>
        
        <!-- Clientes (Menu Expansível) -->
        <div class="space-y-1">
            <button onclick="toggleSubmenu('clientes')" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg <?= in_array($currentPage, ['clientes.php', 'cliente_form.php', 'cliente_detalhes.php', 'tags.php']) ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-users w-5"></i>
                    <span>Clientes</span>
                </div>
                <i id="clientes-icon" class="fas fa-chevron-down text-sm transition-transform"></i>
            </button>
            
            <div id="clientes-submenu" class="hidden pl-8 space-y-1">
                <a href="/clientes" 
                   class="flex items-center space-x-3 px-4 py-2 rounded-lg text-sm <?= in_array($currentPage, ['clientes.php', 'cliente_form.php', 'cliente_detalhes.php']) ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
                    <i class="fas fa-list w-4"></i>
                    <span>Listar Clientes</span>
                </a>
                
                <a href="/tags" 
                   class="flex items-center space-x-3 px-4 py-2 rounded-lg text-sm <?= $currentPage === 'tags.php' ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
                    <i class="fas fa-tags w-4"></i>
                    <span>Tags / Categorias</span>
                </a>
            </div>
        </div>
        
        <!-- Configurações (Menu Expansível) -->
        <div class="space-y-1">
            <button onclick="toggleSubmenu('config')" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-blue-700 transition">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-cog w-5"></i>
                    <span>Configurações</span>
                </div>
                <i id="config-icon" class="fas fa-chevron-down text-sm transition-transform"></i>
            </button>
            
            <div id="config-submenu" class="hidden pl-8 space-y-1">
                <a href="/perfil" 
                   class="flex items-center space-x-3 px-4 py-2 rounded-lg text-sm <?= $currentPage === 'perfil.php' ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
                    <i class="fas fa-user-circle w-4"></i>
                    <span>Meu Perfil</span>
                </a>
                
                <a href="/agenda" 
                   class="flex items-center space-x-3 px-4 py-2 rounded-lg text-sm <?= $currentPage === 'agenda.php' ? 'bg-blue-700 text-white font-medium' : 'hover:bg-blue-700' ?> transition">
                    <i class="fas fa-calendar-alt w-4"></i>
                    <span>Minha Agenda</span>
                </a>
            </div>
        </div>
        
        <div class="pt-4 mt-4 border-t border-blue-700">
            <a href="/logout" 
               class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span>Sair</span>
            </a>
        </div>
    </nav>
    
    <!-- Footer Sidebar -->
    <div class="p-4 border-t border-blue-700 text-xs text-blue-200">
        <p>v2.7.0</p>
        <p>Por <a href="https://dantetesta.com.br" target="_blank" class="hover:text-white">Dante Testa</a></p>
    </div>
    
</aside>

<script>
    // Toggle submenu (define apenas se não existir)
    if (typeof toggleSubmenu === 'undefined') {
        function toggleSubmenu(menuId) {
            const submenu = document.getElementById(menuId + '-submenu');
            const icon = document.getElementById(menuId + '-icon');
            
            if (submenu && icon) {
                if (submenu.classList.contains('hidden')) {
                    submenu.classList.remove('hidden');
                    icon.classList.add('rotate-180');
                } else {
                    submenu.classList.add('hidden');
                    icon.classList.remove('rotate-180');
                }
            }
        }
    }
    
    // Abre submenu automaticamente se estiver em uma das páginas
    (function() {
        const currentPage = '<?= $currentPage ?>';
        console.log('📋 Página atual:', currentPage);
        
        // Submenu Clientes
        if (['clientes.php', 'cliente_form.php', 'cliente_detalhes.php', 'tags.php'].includes(currentPage)) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', abrirSubmenuClientes);
            } else {
                abrirSubmenuClientes();
            }
        }
        
        // Submenu Configurações
        if (currentPage === 'perfil.php' || currentPage === 'agenda.php') {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', abrirSubmenuConfig);
            } else {
                abrirSubmenuConfig();
            }
        }
        
        function abrirSubmenuClientes() {
            const submenu = document.getElementById('clientes-submenu');
            const icon = document.getElementById('clientes-icon');
            if (submenu && icon) {
                console.log('✅ Abrindo submenu Clientes');
                submenu.classList.remove('hidden');
                icon.classList.add('rotate-180');
            }
        }
        
        function abrirSubmenuConfig() {
            const submenu = document.getElementById('config-submenu');
            const icon = document.getElementById('config-icon');
            if (submenu && icon) {
                console.log('✅ Abrindo submenu Configurações');
                submenu.classList.remove('hidden');
                icon.classList.add('rotate-180');
            }
        }
    })();
</script>
