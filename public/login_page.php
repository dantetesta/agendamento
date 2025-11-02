<?php
/**
 * Página de Login - Agenda Professor
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 17:50
 */

// Debug - remover depois
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../core/Auth.php';
    require_once __DIR__ . '/../core/Helpers.php';
    require_once __DIR__ . '/../core/CSRF.php';
    require_once __DIR__ . '/../core/ReCaptcha.php';
    
    // Se já estiver logado, redireciona
    if (Auth::check()) {
        redirect('/dashboard');
    }
    
    $theme = require __DIR__ . '/../config/theme.php';
} catch (Exception $e) {
    die('Erro: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= $theme['app_name'] ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/recaptcha-fix.css">
    
    <?php
    // reCAPTCHA v3
    $recaptcha = new ReCaptcha();
    echo $recaptcha->renderScript();
    ?>
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo e Nome do App -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="<?= $theme['logo']['icon'] ?> mr-2"></i>
                <?= $theme['app_name'] ?>
            </h1>
            <p class="text-xl text-blue-200 mt-4">Faça seu login</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            
            <!-- Mensagens -->
            <?php if ($error = flash('error')): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                        <p class="text-red-700"><?= sanitize($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success = flash('success')): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <p class="text-green-700"><?= sanitize($success) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <form action="/login" method="POST" class="space-y-6" id="loginForm">
                
                <?php
                // Token CSRF
                echo CSRF::field();
                ?>
                
                <!-- E-mail -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                        E-mail
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                           placeholder="seu@email.com"
                           required
                           autofocus>
                </div>
                
                <!-- Senha -->
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>
                        Senha
                    </label>
                    <input type="password" 
                           id="senha" 
                           name="senha" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                           placeholder="••••••••"
                           required>
                </div>
                
                <!-- Lembrar e Esqueci senha -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="lembrar" 
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                        <span class="text-gray-600">Lembrar-me</span>
                    </label>
                    <a href="/esqueci-senha" class="text-blue-600 hover:text-blue-700 font-medium">
                        Esqueci minha senha
                    </a>
                </div>
                
                <!-- Botão -->
                <button type="submit" 
                        id="submitBtn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Entrar
                </button>
            </form>
            
            <?php
            // Script reCAPTCHA no submit
            if ($recaptcha->isEnabled()) {
                echo $recaptcha->renderFormScript('loginForm', 'login');
            }
            ?>
            
            <!-- Registro -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Não tem uma conta? 
                    <a href="/registro" class="text-blue-600 hover:underline font-medium">
                        Criar conta grátis
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Voltar -->
        <div class="mt-6 text-center">
            <a href="/" class="text-blue-200 hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para home
            </a>
        </div>
    </div>

</body>
</html>
