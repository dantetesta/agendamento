<?php
/**
 * Redirecionamento - Não deve ser acessado diretamente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:55
 * 
 * Este arquivo não deve ser acessado diretamente.
 * Use as rotas amigáveis: / ou /login
 */

require_once __DIR__ . '/../core/Helpers.php';

// Carrega configurações do app
$appConfig = require __DIR__ . '/../config/app.php';
$appName = $appConfig['name'] ?? 'Agenda Master';
$appIcon = $appConfig['logo']['icon'] ?? 'fas fa-calendar-check';

// Redireciona para landing page
redirect('/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema inteligente de agendamento para professores. Gerencie sua disponibilidade e compromissos de forma simples e eficiente.">
    <meta name="keywords" content="agenda, professor, agendamento, aulas, horários">
    <meta name="author" content="Dante Testa">
    <title>Agenda do Professor Inteligente - Organize suas aulas com facilidade</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SEO e Open Graph -->
    <meta property="og:title" content="Agenda do Professor Inteligente">
    <meta property="og:description" content="Sistema inteligente de agendamento para professores">
    <meta property="og:type" content="website">
    
    <style>
        @media (prefers-reduced-motion: no-preference) {
            .animate-fade-in {
                animation: fadeIn 0.8s ease-in;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen">
    
    <!-- Header/Navbar -->
    <nav class="fixed top-0 left-0 right-0 bg-white/80 backdrop-blur-md shadow-sm z-50">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center space-x-2 text-2xl font-bold text-gray-900 hover:text-blue-600 transition-colors">
                    <i class="<?= $appIcon ?> text-blue-600"></i>
                    <span><?= $appName ?></span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="#login" class="text-gray-600 hover:text-blue-600 font-medium transition-colors">
                        Login
                    </a>
                    <a href="/registro" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                        Criar Conta
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center px-4 py-12 pt-24">
        <div class="max-w-6xl w-full">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                
                <!-- Conteúdo Principal -->
                <div class="animate-fade-in">
                    <div class="inline-flex items-center bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                        <i class="fas fa-sparkles mr-2"></i>
                        Sistema Inteligente de Agendamento
                    </div>
                    
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                        Agenda do Professor
                        <span class="text-blue-600">Inteligente</span>
                    </h1>
                    
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Gerencie sua disponibilidade, organize suas aulas e mantenha seus compromissos sempre em dia. 
                        Simples, rápido e eficiente.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/public/cadastro.php" 
                           class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-4 rounded-lg transition duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-user-plus mr-2"></i>
                            Criar Conta Grátis
                        </a>
                        <a href="#login" 
                           class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 font-semibold px-8 py-4 rounded-lg transition duration-200 border-2 border-gray-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Já tenho conta
                        </a>
                    </div>
                    
                    <!-- Features -->
                    <div class="grid grid-cols-3 gap-6 mt-12">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mb-2">
                                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Calendário Inteligente</p>
                        </div>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mb-2">
                                <i class="fas fa-clock text-green-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Gestão de Horários</p>
                        </div>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mb-2">
                                <i class="fas fa-bell text-purple-600 text-xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Notificações</p>
                        </div>
                    </div>
                </div>
                
                <!-- Imagem Hero + Formulário de Login -->
                <div class="space-y-6 animate-fade-in">
                    <!-- Imagem Hero -->
                    <div class="rounded-2xl overflow-hidden shadow-2xl">
                        <img src="/uploads/hero-dashboard.jpg" 
                             alt="Sistema de Agendamentos - Organize seus agendamentos de forma inteligente" 
                             class="w-full h-auto object-cover">
                    </div>
                    
                    <!-- Formulário de Login -->
                    <div id="login" class="bg-white rounded-2xl shadow-2xl p-8">
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                                <i class="fas fa-user text-white text-2xl"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-800">Acessar Conta</h2>
                            <p class="text-gray-600 mt-2">Entre com suas credenciais</p>
                        </div>
                    
                    <?php if ($error = flash('error')): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                                <p class="text-red-700"><?= sanitize($error) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success = flash('success')): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                                <p class="text-green-700"><?= sanitize($success) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/public/login.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-1"></i>
                                E-mail
                            </label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="seu@email.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                Senha
                            </label>
                            <input type="password" name="senha" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center">
                                <input type="checkbox" name="lembrar" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-600">Lembrar-me</span>
                            </label>
                            <a href="/esqueci-senha" class="text-blue-600 hover:text-blue-700 font-medium">
                                Esqueci minha senha
                            </a>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Entrar
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-gray-600">
                            Não tem uma conta? 
                            <a href="/public/cadastro.php" class="text-blue-600 hover:text-blue-700 font-semibold">
                                Cadastre-se grátis
                            </a>
                        </p>
                    </div>
                    </div>
                    <!-- Fim do formulário de login -->
                </div>
                <!-- Fim da coluna direita -->
                
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <p class="text-gray-400">
                &copy; <?= date('Y') ?> Agenda do Professor Inteligente - Todos os direitos reservados
            </p>
            <p class="text-gray-500 mt-2 text-sm">
                Desenvolvido por <a href="https://dantetesta.com.br" target="_blank" class="text-blue-400 hover:text-blue-300">Dante Testa</a>
            </p>
        </div>
    </footer>
    
</body>
</html>
