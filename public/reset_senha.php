<?php
/**
 * Reset de Senha - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 14:47
 * Sistema completo de recuperação de senha com validação forte
 */

require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/RateLimit.php';
require_once __DIR__ . '/../core/ReCaptcha.php';
require_once __DIR__ . '/../core/SecurityLogger.php';
require_once __DIR__ . '/../core/Mailer.php';

// Carrega configurações
$config = require __DIR__ . '/../config/app.php';
$theme = require __DIR__ . '/../config/theme.php';

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../app/Models/Professor.php';

Auth::requireGuest();

$errors = [];
$success = false;
$token = $_GET['token'] ?? null;

// Se tem token, é a página de redefinir senha
if ($token) {
    $professorModel = new Professor();
    $resetData = $professorModel->validateResetToken($token);
    
    if (!$resetData) {
        $errors[] = 'Token inválido ou expirado. Solicite um novo link de recuperação.';
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
        // Validação CSRF
        if (!CSRF::validate()) {
            $errors[] = 'Token de segurança inválido. Recarregue a página.';
        }
        
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        // Validação de senha forte
        if (empty($senha)) {
            $errors[] = 'A senha é obrigatória.';
        } else {
            if (strlen($senha) < 8) {
                $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
            }
            if (!preg_match('/[A-Z]/', $senha)) {
                $errors[] = 'A senha deve conter pelo menos uma letra maiúscula.';
            }
            if (!preg_match('/[a-z]/', $senha)) {
                $errors[] = 'A senha deve conter pelo menos uma letra minúscula.';
            }
            if (!preg_match('/[0-9]/', $senha)) {
                $errors[] = 'A senha deve conter pelo menos um número.';
            }
            if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
                $errors[] = 'A senha deve conter pelo menos um caractere especial.';
            }
        }
        
        if ($senha !== $confirmarSenha) {
            $errors[] = 'As senhas não coincidem.';
        }
        
        if (empty($errors)) {
            // Busca professor
            $professor = $professorModel->findByEmail($resetData['email']);
            
            if ($professor) {
                // Atualiza senha
                $professorModel->updatePassword($professor['id'], hashPassword($senha));
                
                // Deleta token usado
                $professorModel->deleteResetToken($token);
                
                // Log
                $logger = new SecurityLogger();
                $logger->logPasswordReset($professor['id'], $resetData['email']);
                
                setFlash('success', 'Senha alterada com sucesso! Faça login com sua nova senha.');
                redirect('/login');
                exit;
            } else {
                $errors[] = 'Erro ao redefinir senha. Tente novamente.';
            }
        }
    }
    
} else {
    // Página de solicitar reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validação CSRF
        if (!CSRF::validate()) {
            $errors[] = 'Token de segurança inválido. Recarregue a página.';
        }
        
        // Rate Limiting (10 tentativas por hora)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = 'reset_senha_' . $ip;
        $rateLimit = new RateLimit();
        
        if (!$rateLimit->check($rateLimitKey, 10, 3600)) {
            $remaining = ceil($rateLimit->getRemainingTime($rateLimitKey, 3600) / 60);
            $errors[] = "Muitas tentativas. Tente novamente em {$remaining} minuto(s).";
        }
        
        // reCAPTCHA v3
        $recaptcha = new ReCaptcha();
        if ($recaptcha->isEnabled() && empty($errors)) {
            $token = $_POST['recaptcha_token'] ?? '';
            $result = $recaptcha->verify($token, 'reset_password');
            
            if (!$result['success']) {
                $rateLimit->hit($rateLimitKey);
                $errors[] = 'Verificação de segurança falhou. Tente novamente.';
            }
        }
        
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errors[] = 'O e-mail é obrigatório.';
        } elseif (!validEmail($email)) {
            $errors[] = 'E-mail inválido.';
        }
        
        if (empty($errors)) {
            $rateLimit->hit($rateLimitKey);
            
            try {
                $professorModel = new Professor();
                $professor = $professorModel->findByEmail($email);
                
                if ($professor) {
                    // Cria token de reset
                    $resetToken = $professorModel->createResetToken($email);
                
                // Monta link de reset
                $resetLink = baseUrl("/esqueci-senha?token={$resetToken}");
                
                // Envia e-mail usando a classe Mailer existente
                $mailer = new Mailer();
                $emailBody = "
                    <h2>🔐 Redefinir Senha</h2>
                    <p>Olá, <strong>{$professor['nome']}</strong>!</p>
                    <p>Você solicitou a redefinição de senha da sua conta.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' style='background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;'>
                            Redefinir Minha Senha
                        </a>
                    </p>
                    <p style='color: #dc2626; font-weight: bold;'>⏰ Este link expira em 1 hora.</p>
                    <p style='color: #6b7280; font-size: 14px;'>
                        Se você não solicitou esta redefinição, ignore este e-mail. Sua senha permanecerá inalterada.
                    </p>
                    <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                    <p style='color: #6b7280; font-size: 12px;'>
                        Por segurança, nunca compartilhe este link com outras pessoas.
                    </p>
                ";
                
                $enviado = $mailer->send(
                    $email,
                    "Redefinir Senha - {$theme['app_name']}",
                    $emailBody
                );
                
                if ($enviado) {
                    $success = true;
                    
                    // Log
                    $logger = new SecurityLogger();
                    $logger->logPasswordResetRequest($email);
                } else {
                    $errors[] = 'Erro ao enviar e-mail. Tente novamente em alguns minutos.';
                }
                } else {
                    // Por segurança, não informamos se o e-mail existe ou não
                    // Sempre mostramos sucesso
                    $success = true;
                    
                    // Log
                    $logger = new SecurityLogger();
                    $logger->logPasswordResetRequest($email);
                }
                
            } catch (Exception $e) {
                // Captura erro e mostra mensagem amigável
                error_log("Erro no reset de senha: " . $e->getMessage());
                $errors[] = "Erro ao processar solicitação: " . $e->getMessage();
                $errors[] = "Verifique se executou a migração: migrate_password_resets.php";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $token ? 'Redefinir' : 'Recuperar' ?> Senha - <?= $theme['app_name'] ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/recaptcha-fix.css">
    <link rel="stylesheet" href="/public/assets/css/password-validator-v2.css?v=<?= time() ?>">
    
    <?php
    // reCAPTCHA v3
    $recaptcha = new ReCaptcha();
    echo $recaptcha->renderScript();
    ?>
    
    <script src="/public/assets/js/password-validator-v2.js?v=<?= time() ?>"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-md w-full">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <a href="/login" class="inline-flex items-center text-blue-600 hover:text-blue-700 mb-8">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para login
            </a>
            
            <!-- Nome do App -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900">
                    <i class="<?= $theme['logo']['icon'] ?> text-blue-600 mr-2"></i>
                    <?= $theme['app_name'] ?>
                </h1>
            </div>
            
            <h2 class="text-3xl font-bold text-gray-800 mb-2">
                <?= $token ? 'Redefinir Senha' : 'Recuperar Senha' ?>
            </h2>
            <p class="text-gray-600">
                <?= $token ? 'Digite sua nova senha' : 'Enviaremos um link para seu e-mail' ?>
            </p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            
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
            
            <?php if ($success && !$token): ?>
                <!-- Mensagem de Sucesso - E-mail Enviado -->
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <div class="flex-1">
                            <h3 class="text-green-800 font-semibold mb-2">
                                ✅ E-mail enviado com sucesso!
                            </h3>
                            <p class="text-green-700 mb-3">
                                Enviamos um link de recuperação para seu e-mail.
                            </p>
                            <div class="bg-white border border-green-200 rounded-lg p-4 mb-3">
                                <p class="text-sm text-gray-700 mb-2">
                                    <strong>📧 Próximos passos:</strong>
                                </p>
                                <ol class="text-sm text-gray-600 space-y-1 ml-4 list-decimal">
                                    <li>Verifique sua caixa de entrada</li>
                                    <li>Procure também na pasta de SPAM</li>
                                    <li>Clique no link recebido</li>
                                    <li>Crie sua nova senha</li>
                                </ol>
                            </div>
                            <p class="text-xs text-green-600">
                                ⏰ O link expira em 1 hora
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar para o login
                    </a>
                </div>
                
            <?php elseif ($token && empty($errors)): ?>
                <!-- Formulário de Nova Senha -->
                <form method="POST" class="space-y-6" id="resetForm">
                    <?php echo CSRF::field(); ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-1"></i>
                            Nova Senha *
                        </label>
                        <input type="password" 
                               id="nova_senha"
                               name="senha" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent validate-password"
                               placeholder="Digite uma senha forte">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-1"></i>
                            Confirmar Nova Senha *
                        </label>
                        <input type="password" 
                               id="confirmar_nova_senha"
                               name="confirmar_senha" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent validate-password"
                               placeholder="Digite a senha novamente">
                    </div>
                    
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                        <i class="fas fa-check mr-2"></i>
                        Redefinir Senha
                    </button>
                </form>
                
            <?php else: ?>
                <!-- Formulário de Solicitar Reset -->
                <form method="POST" class="space-y-6" id="requestForm">
                    <?php echo CSRF::field(); ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-1"></i>
                            Seu E-mail *
                        </label>
                        <input type="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="seu@email.com">
                        <p class="text-xs text-gray-500 mt-1">
                            Digite o e-mail cadastrado na sua conta
                        </p>
                    </div>
                    
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar Link de Recuperação
                    </button>
                </form>
            <?php endif; ?>
            
        </div>
        
    </div>
    
    <?php
    // Script reCAPTCHA no submit
    if ($recaptcha->isEnabled()) {
        if ($token) {
            echo $recaptcha->renderFormScript('resetForm', 'reset_password');
        } else {
            echo $recaptcha->renderFormScript('requestForm', 'reset_password');
        }
    }
    ?>
    
</body>
</html>
