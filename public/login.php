<?php
/**
 * Login - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 14:55
 * Versão limpa e otimizada com tratamento de erros
 */

// Habilita exibição de erros temporariamente
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/RateLimit.php';
require_once __DIR__ . '/../core/ReCaptcha.php';
require_once __DIR__ . '/../core/SecurityLogger.php';
require_once __DIR__ . '/../core/AccountLock.php';
require_once __DIR__ . '/../app/Models/Professor.php';

// Configurações
$config = require __DIR__ . '/../config/app.php';
$theme = require __DIR__ . '/../config/theme.php';

// Se já estiver logado, redireciona
if (Auth::check()) {
    redirect('/dashboard');
    exit;
}

$errors = [];

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Validação CSRF
    if (!CSRF::validate()) {
        $errors[] = 'Token de segurança inválido. Recarregue a página.';
    }
    
    // 2. Rate Limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitKey = 'login_' . $ip;
    $rateLimit = new RateLimit();
    
    if (!$rateLimit->check($rateLimitKey, 5, 900)) {
        $remaining = ceil($rateLimit->getRemainingTime($rateLimitKey, 900) / 60);
        $errors[] = "Muitas tentativas. Tente novamente em {$remaining} minuto(s).";
    }
    
    // 3. reCAPTCHA
    $recaptcha = new ReCaptcha();
    if ($recaptcha->isEnabled() && empty($errors)) {
        $token = $_POST['recaptcha_token'] ?? '';
        $result = $recaptcha->verify($token, 'login');
        
        if (!$result['success']) {
            $rateLimit->hit($rateLimitKey);
            $errors[] = 'Verificação de segurança falhou. Tente novamente.';
        }
    }
    
    // 4. Validação de campos
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $errors[] = 'Preencha todos os campos.';
    } elseif (!validEmail($email)) {
        $errors[] = 'E-mail inválido.';
    }
    
    // 5. Verificação de bloqueio
    if (empty($errors)) {
        $accountLock = new AccountLock();
        
        if ($accountLock->isLocked($email)) {
            $remaining = $accountLock->getRemainingTime($email);
            $errors[] = "Conta bloqueada. Tente novamente em {$remaining} minutos.";
        }
    }
    
    // 6. Autenticação
    if (empty($errors)) {
        $professorModel = new Professor();
        $professor = $professorModel->findByEmail($email);
        
        if ($professor && verifyPassword($senha, $professor['senha_hash'])) {
            // Login bem-sucedido
            $accountLock->resetAttempts($email);
            
            // Garante que is_admin existe (compatibilidade)
            if (!isset($professor['is_admin'])) {
                $professor['is_admin'] = 0;
            }
            
            Auth::login($professor['id'], $professor);
            
            // Log
            $logger = new SecurityLogger();
            $logger->logLogin($professor['id'], $email, true);
            
            redirect('/dashboard');
            exit;
            
        } else {
            // Login falhou
            $rateLimit->hit($rateLimitKey);
            
            if ($professor) {
                $accountLock->recordFailedAttempt($email);
            }
            
            // Log
            $logger = new SecurityLogger();
            $logger->logLogin(null, $email, false);
            
            $errors[] = 'E-mail ou senha incorretos.';
        }
    }
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
    $recaptcha = new ReCaptcha();
    if ($recaptcha->isEnabled()) {
        echo $recaptcha->renderScript();
    }
    ?>
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-800 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-md w-full">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="<?= $theme['logo']['icon'] ?> mr-2"></i>
                <?= $theme['app_name'] ?>
            </h1>
            <p class="text-blue-200">Faça login para continuar</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            
            <!-- Mensagem de sucesso (reset de senha) -->
            <?php if (hasFlash('success')): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-green-700">
                                <?= getFlash('success') ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Erros -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                        <div class="flex-1">
                            <ul class="text-red-700 text-sm space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= sanitize($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <form method="POST" id="loginForm" class="space-y-6">
                <?php echo CSRF::field(); ?>
                
                <!-- E-mail -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1"></i>
                        E-mail
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                           placeholder="seu@email.com"
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                
                <!-- Senha -->
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1"></i>
                        Senha
                    </label>
                    <input type="password" 
                           id="senha" 
                           name="senha" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                           placeholder="••••••••">
                </div>
                
                <!-- Esqueci senha -->
                <div class="text-right">
                    <a href="/esqueci-senha" class="text-sm text-blue-600 hover:text-blue-700">
                        Esqueci minha senha
                    </a>
                </div>
                
                <!-- Botão -->
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Entrar
                </button>
            </form>
            
            <!-- Registro -->
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Não tem uma conta?
                    <a href="/registro" class="text-blue-600 hover:text-blue-700 font-medium">
                        Criar conta grátis
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <?php
    if ($recaptcha->isEnabled()) {
        echo $recaptcha->renderFormScript('loginForm', 'login');
    }
    ?>
    
</body>
</html>
