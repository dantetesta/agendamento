<?php
/**
 * Landing Page - Agenda Professor SaaS
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:52
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

// Se já estiver logado, redireciona para dashboard
if (Auth::check()) {
    redirect('/dashboard');
}

$theme = require __DIR__ . '/../config/theme.php';
$plans = require __DIR__ . '/../config/plans.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $theme['app_name'] ?> - Sistema de Agendamento para Professores</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-calendar-check text-3xl text-blue-600"></i>
                    <h1 class="text-2xl font-bold text-gray-900"><?= $theme['app_name'] ?></h1>
                </div>
                
                <nav class="hidden md:flex space-x-8">
                    <a href="#recursos" class="text-gray-700 hover:text-blue-600 transition">Recursos</a>
                    <a href="#planos" class="text-gray-700 hover:text-blue-600 transition">Planos</a>
                    <a href="#contato" class="text-gray-700 hover:text-blue-600 transition">Contato</a>
                </nav>
                
                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-gray-700 hover:text-blue-600 transition font-medium">
                        Entrar
                    </a>
                    <a href="/registro" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition font-medium">
                        Começar Grátis
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-5xl font-bold mb-6">
                        Organize seus agendamentos de forma inteligente
                    </h2>
                    <p class="text-xl mb-8 text-gray-100">
                        Sistema completo para gerenciar seus agendamentos, horários e clientes em um só lugar. 
                        Ideal para professores, consultores, médicos, terapeutas e qualquer profissional que atende pessoas.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/login" 
                           class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 font-semibold px-8 py-4 rounded-lg transition duration-200 border-2 border-gray-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Já tenho conta
                        </a>
                        <a href="#planos" class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-blue-600 transition text-center">
                            Ver Planos
                        </a>
                    </div>
                    <p class="mt-6 text-sm text-gray-200">
                        <i class="fas fa-check-circle mr-2"></i>
                        Sem cartão de crédito • Teste grátis • Cancele quando quiser
                    </p>
                </div>
                
                <div class="hidden md:block">
                    <!-- Imagem Hero do Dashboard -->
                    <div class="rounded-2xl overflow-hidden shadow-2xl">
                        <img src="/public/dashboard-hero.jpg" 
                             alt="Sistema de Agendamentos - Organize seus agendamentos de forma inteligente" 
                             class="w-full h-auto object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                    </div>
                    
                    <!-- Backup: Preview do Dashboard (caso imagem não carregue) -->
                    <div class="bg-white rounded-2xl shadow-2xl p-8 hidden">
                        <!-- Preview do Dashboard -->
                        <div class="relative">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg p-6">
                                <!-- Header do Dashboard -->
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800">Professor</p>
                                            <p class="text-xs text-gray-600">Bem-vindo!</p>
                                        </div>
                                    </div>
                                    <div class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">
                                        <i class="fas fa-plus mr-1"></i> Novo
                                    </div>
                                </div>
                                
                                <!-- Cards de Estatísticas -->
                                <div class="grid grid-cols-3 gap-3 mb-6">
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-xs text-gray-600 mb-1">Total</p>
                                                <p class="text-2xl font-bold text-gray-800">12</p>
                                            </div>
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-calendar text-blue-600"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-xs text-gray-600 mb-1">Hoje</p>
                                                <p class="text-2xl font-bold text-gray-800">3</p>
                                            </div>
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-check text-green-600"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-xs text-gray-600 mb-1">Próximos</p>
                                                <p class="text-2xl font-bold text-gray-800">8</p>
                                            </div>
                                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-arrow-right text-purple-600"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mini Calendário -->
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="font-bold text-gray-800">Calendário</h3>
                                        <div class="flex space-x-2">
                                            <button class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center">
                                                <i class="fas fa-chevron-left text-xs text-gray-600"></i>
                                            </button>
                                            <button class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center">
                                                <i class="fas fa-chevron-right text-xs text-gray-600"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                        <div class="text-gray-500 font-bold py-1">D</div>
                                        <div class="text-gray-500 font-bold py-1">S</div>
                                        <div class="text-gray-500 font-bold py-1">T</div>
                                        <div class="text-gray-500 font-bold py-1">Q</div>
                                        <div class="text-gray-500 font-bold py-1">Q</div>
                                        <div class="text-gray-500 font-bold py-1">S</div>
                                        <div class="text-gray-500 font-bold py-1">S</div>
                                        <div class="text-gray-400 py-1">29</div>
                                        <div class="text-gray-400 py-1">30</div>
                                        <div class="text-gray-800 py-1">1</div>
                                        <div class="text-gray-800 py-1">2</div>
                                        <div class="text-gray-800 py-1">3</div>
                                        <div class="text-gray-800 py-1">4</div>
                                        <div class="text-gray-800 py-1">5</div>
                                        <div class="text-gray-800 py-1">6</div>
                                        <div class="text-gray-800 py-1">7</div>
                                        <div class="text-gray-800 py-1">8</div>
                                        <div class="text-gray-800 py-1">9</div>
                                        <div class="text-gray-800 py-1">10</div>
                                        <div class="text-gray-800 py-1">11</div>
                                        <div class="text-gray-800 py-1">12</div>
                                        <div class="text-gray-800 py-1">13</div>
                                        <div class="text-gray-800 py-1">14</div>
                                        <div class="bg-blue-600 text-white rounded py-1 font-bold">15</div>
                                        <div class="text-gray-800 py-1">16</div>
                                        <div class="text-gray-800 py-1">17</div>
                                        <div class="text-gray-800 py-1">18</div>
                                        <div class="text-gray-800 py-1">19</div>
                                        <div class="text-gray-800 py-1">20</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recursos -->
    <section id="recursos" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Tudo que você precisa em um só lugar
                </h2>
                <p class="text-xl text-gray-600">
                    Recursos poderosos para facilitar sua rotina
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Recurso 1 -->
                <div class="card-hover bg-gray-50 p-8 rounded-xl">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-calendar-alt text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Calendário Inteligente</h3>
                    <p class="text-gray-600">
                        Visualize todos os seus agendamentos em um calendário interativo e intuitivo.
                    </p>
                </div>
                
                <!-- Recurso 2 -->
                <div class="card-hover bg-gray-50 p-8 rounded-xl">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-clock text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Gestão de Horários</h3>
                    <p class="text-gray-600">
                        Configure sua disponibilidade e deixe o sistema sugerir os melhores horários.
                    </p>
                </div>
                
                <!-- Recurso 3 -->
                <div class="card-hover bg-gray-50 p-8 rounded-xl">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-users text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Gestão de Clientes</h3>
                    <p class="text-gray-600">
                        Cadastre e organize seus clientes: alunos, pacientes, leads, contatos ou qualquer pessoa que você atende. 
                        Histórico completo de agendamentos em um só lugar.
                    </p>
                </div>
                
                <!-- Recurso 4 -->
                <div class="card-hover bg-gray-50 p-8 rounded-xl">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-bell text-3xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Notificações</h3>
                    <p class="text-gray-600">
                        Receba lembretes automáticos por e-mail sobre suas aulas.
                    </p>
                </div>
                
                <!-- Recurso 5 -->
                <div class="card-hover bg-gray-50 p-8 rounded-xl">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-3xl text-red-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Relatórios</h3>
                    <p class="text-gray-600">
                        Acompanhe estatísticas e métricas dos seus agendamentos.
                    </p>
                </div>
                
                <!-- Recurso 6 -->
                <div class="card-hover bg-gray-50 p-8 rounded-xl">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-mobile-alt text-3xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">100% Responsivo</h3>
                    <p class="text-gray-600">
                        Acesse de qualquer dispositivo: computador, tablet ou celular.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Planos -->
    <section id="planos" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Escolha o plano ideal para você
                </h2>
                <p class="text-xl text-gray-600">
                    Comece grátis e faça upgrade quando precisar
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <?php foreach ($plans['plans'] as $slug => $plan): ?>
                <div class="bg-white rounded-2xl shadow-xl p-8 <?= $plan['popular'] ?? false ? 'ring-4 ring-blue-600 relative' : '' ?>">
                    <?php if ($plan['popular'] ?? false): ?>
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                            <span class="bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-bold">
                                MAIS POPULAR
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mb-8">
                        <h3 class="text-3xl font-bold text-gray-900 mb-2"><?= $plan['name'] ?></h3>
                        <p class="text-gray-600 mb-6"><?= $plan['description'] ?></p>
                        
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-gray-900">
                                R$ <?= number_format($plan['price'], 2, ',', '.') ?>
                            </span>
                            <span class="text-gray-600">/mês</span>
                        </div>
                        
                        <a href="/registro?plan=<?= $slug ?>" 
                           class="block w-full <?= $plan['popular'] ?? false ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-900 hover:bg-gray-800' ?> text-white px-8 py-4 rounded-lg font-bold text-lg transition">
                            <?= $plan['cta'] ?>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-3"></i>
                            <span class="text-gray-700">
                                <?= $plan['limits']['agendamentos_ativos'] === -1 ? 'Agendamentos ilimitados' : $plan['limits']['agendamentos_ativos'] . ' agendamentos ativos' ?>
                            </span>
                        </div>
                        
                        <?php if ($plan['features']['calendario']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-3"></i>
                            <span class="text-gray-700">Calendário interativo</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($plan['features']['notificacoes_email']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-3"></i>
                            <span class="text-gray-700">Notificações por e-mail</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($plan['features']['relatorios']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-3"></i>
                            <span class="text-gray-700">Relatórios avançados</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-3"></i>
                            <span class="text-gray-700">Suporte <?= $plan['features']['suporte'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-6">
                Pronto para organizar seus agendamentos?
            </h2>
            <p class="text-xl mb-8 text-gray-100">
                Comece grátis agora mesmo. Não precisa de cartão de crédito.
            </p>
            <a href="/registro" class="inline-block bg-white text-blue-600 px-12 py-4 rounded-lg font-bold text-xl hover:bg-gray-100 transition">
                <i class="fas fa-rocket mr-2"></i>
                Criar Conta Grátis
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4"><?= $theme['app_name'] ?></h3>
                    <p class="text-gray-400">
                        Sistema completo de agendamento para professores.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Produto</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#recursos" class="hover:text-white transition">Recursos</a></li>
                        <li><a href="#planos" class="hover:text-white transition">Planos</a></li>
                        <li><a href="/login" class="hover:text-white transition">Login</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Suporte</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Ajuda</a></li>
                        <li><a href="#" class="hover:text-white transition">Documentação</a></li>
                        <li><a href="#contato" class="hover:text-white transition">Contato</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Termos de Uso</a></li>
                        <li><a href="#" class="hover:text-white transition">Privacidade</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; 2025 <?= $theme['app_name'] ?>. Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-white hover:underline">Dante Testa</a></p>
            </div>
        </div>
    </footer>

</body>
</html>
