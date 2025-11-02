<?php
/**
 * Página de Registro - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 01/11/2025 21:55
 * Atualização: Adicionado reCAPTCHA v3, CSRF e Rate Limiting
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/RateLimit.php';
require_once __DIR__ . '/../core/ReCaptcha.php';
require_once __DIR__ . '/../app/Models/Professor.php';

// Carrega configurações
$theme = require __DIR__ . '/../config/theme.php';

// Se já estiver logado, redireciona
if (Auth::check()) {
    redirect('/dashboard');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validação CSRF
    if (!CSRF::validate()) {
        $errors[] = 'Token de segurança inválido. Recarregue a página.';
    } else {
        // 2. Rate Limiting (3 registros por hora por IP)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = 'register_' . $ip;
        $rateLimit = new RateLimit();
        
        if (!$rateLimit->check($rateLimitKey, 3, 3600)) {
            $remaining = ceil($rateLimit->getRemainingTime($rateLimitKey, 3600) / 60);
            $errors[] = "Muitas tentativas de registro. Tente novamente em {$remaining} minuto(s).";
        } else {
            // 3. Validação reCAPTCHA v3
            $recaptcha = new ReCaptcha();
            if ($recaptcha->isEnabled()) {
                $token = $_POST['recaptcha_token'] ?? '';
                $result = $recaptcha->verify($token, 'register');
                
                if (!$result['success']) {
                    $rateLimit->hit($rateLimitKey);
                    $errors[] = 'Verificação de segurança falhou. Tente novamente.';
                }
            }
        }
    }
    
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $plano = sanitize($_POST['plano'] ?? 'free');
    
    // Validações
    if (empty($nome)) {
        $errors[] = 'O nome é obrigatório.';
    }
    
    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório.';
    } elseif (!validEmail($email)) {
        $errors[] = 'E-mail inválido.';
    }
    
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
        if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
            $errors[] = 'A senha deve conter pelo menos um caractere especial (!@#$%^&*).';
        }
    }
    
    if ($senha !== $confirmarSenha) {
        $errors[] = 'As senhas não coincidem.';
    }
    
    // Verifica se e-mail já existe
    if (empty($errors)) {
        $professorModel = new Professor();
        
        if ($professorModel->emailExists($email)) {
            $errors[] = 'Este e-mail já está cadastrado.';
        }
    }
    
    // Cria professor
    if (empty($errors)) {
        // Registra tentativa no rate limit
        if (isset($rateLimit) && isset($rateLimitKey)) {
            $rateLimit->hit($rateLimitKey);
        }
        
        $professorId = $professorModel->create([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => hashPassword($senha),
            'plano' => $plano,
        ]);
        
        if ($professorId) {
            // Faz login automático
            $professor = $professorModel->findById($professorId);
            Auth::login($professorId, $professor);
            
            setFlash('success', 'Conta criada com sucesso! Bem-vindo ao Agenda Professor!');
            redirect('/dashboard');
        } else {
            $errors[] = 'Erro ao criar conta. Tente novamente.';
        }
    }
}

$planoSelecionado = $_GET['plan'] ?? 'free';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - <?= $theme['app_name'] ?></title>
    
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
<body class="bg-gradient-to-br from-blue-900 to-blue-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-3xl w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="<?= $theme['logo']['icon'] ?> mr-2"></i>
                <?= $theme['app_name'] ?>
            </h1>
            <h2 class="text-2xl font-bold text-white mt-4">Criar Conta</h2>
            <p class="text-blue-200 mt-2">Comece grátis agora mesmo</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            
            <!-- Erros -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                        <div>
                            <h3 class="font-bold text-red-800 mb-2">Erro ao criar conta:</h3>
                            <ul class="list-disc list-inside text-red-700 text-sm">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <form method="POST" class="space-y-6" id="registerForm">
                <?php
                // Token CSRF
                echo CSRF::field();
                ?>
                <input type="hidden" name="plano" value="<?= $planoSelecionado ?>">
                
                <!-- Grid 2 Colunas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Coluna 1: Dados Pessoais -->
                    <div class="space-y-4">
                        <!-- Nome -->
                        <div>
                            <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-gray-400"></i>
                                Nome Completo
                            </label>
                            <input type="text" 
                                   id="nome" 
                                   name="nome" 
                                   value="<?= $_POST['nome'] ?? '' ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                   placeholder="Seu nome completo"
                                   required>
                        </div>
                        
                        <!-- E-mail -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                E-mail
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= $_POST['email'] ?? '' ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                   placeholder="seu@email.com"
                                   required>
                        </div>
                        
                        <!-- WhatsApp -->
                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fab fa-whatsapp mr-2 text-gray-400"></i>
                                WhatsApp
                            </label>
                            <input type="tel" 
                                   id="whatsapp" 
                                   name="whatsapp" 
                                   value="<?= $_POST['whatsapp'] ?? '' ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                   placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    
                    <!-- Coluna 2: Senhas -->
                    <div class="space-y-4">
                        <!-- Senha -->
                        <div>
                            <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                Senha
                            </label>
                            <input type="password" 
                                   id="senha" 
                                   name="senha" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent validate-password"
                                   placeholder="Digite uma senha forte" 
                                   required>
                        </div>
                        
                        <!-- Confirmar Senha -->
                        <div>
                            <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                Confirmar Senha
                            </label>
                            <input type="password" 
                                   id="confirmar_senha" 
                                   name="confirmar_senha" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent validate-password"
                                   placeholder="Digite a senha novamente" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <!-- Plano Selecionado -->
                <?php if ($planoSelecionado === 'pro'): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-star text-blue-600 mr-3"></i>
                            <div>
                                <p class="font-bold text-blue-900">Plano Profissional Selecionado</p>
                                <p class="text-sm text-blue-700">R$ 50,00/mês • Agendamentos ilimitados</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-3"></i>
                            <div>
                                <p class="font-bold text-green-900">Plano Gratuito</p>
                                <p class="text-sm text-green-700">10 agendamentos ativos • Sem cartão de crédito</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Termos -->
                <div class="flex items-start">
                    <input type="checkbox" 
                           id="termos" 
                           name="termos" 
                           class="mt-1 mr-3"
                           required>
                    <label for="termos" class="text-sm text-gray-600">
                        Eu concordo com os <a href="/termos" target="_blank" class="text-blue-600 hover:underline">Termos de Uso</a> e 
                        <a href="/privacidade" target="_blank" class="text-blue-600 hover:underline">Política de Privacidade</a>
                    </label>
                </div>
                
                <!-- Botão -->
                <button type="submit" 
                        id="submitBtn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition">
                    <i class="fas fa-rocket mr-2"></i>
                    Criar Conta Grátis
                </button>
            </form>
            
            <?php
            // Script reCAPTCHA no submit
            if ($recaptcha->isEnabled()) {
                echo $recaptcha->renderFormScript('registerForm', 'register');
            }
            ?>
            
            <!-- Login -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Já tem uma conta? 
                    <a href="/login" class="text-blue-600 hover:underline font-medium">
                        Fazer Login
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

    <script>
        // Toggle mostrar/ocultar senha
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Verificar força da senha
        function checkPasswordStrength() {
            const password = document.getElementById('senha').value;
            const bars = ['strength-1', 'strength-2', 'strength-3', 'strength-4'];
            const text = document.getElementById('strength-text');
            
            // Reset
            bars.forEach(bar => {
                document.getElementById(bar).className = 'h-1 flex-1 bg-gray-200 rounded';
            });
            
            if (password.length === 0) {
                text.textContent = 'Digite uma senha';
                text.className = 'text-xs text-gray-500';
                return;
            }
            
            let strength = 0;
            
            // Critérios
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Normaliza para 4 níveis
            const level = Math.min(Math.ceil(strength / 1.25), 4);
            
            // Aplica cores
            const colors = [
                { bg: 'bg-red-500', text: 'Fraca', class: 'text-red-600' },
                { bg: 'bg-orange-500', text: 'Média', class: 'text-orange-600' },
                { bg: 'bg-yellow-500', text: 'Boa', class: 'text-yellow-600' },
                { bg: 'bg-green-500', text: 'Forte', class: 'text-green-600' }
            ];
            
            for (let i = 0; i < level; i++) {
                document.getElementById(bars[i]).className = `h-1 flex-1 ${colors[level-1].bg} rounded`;
            }
            
            text.textContent = `Senha ${colors[level-1].text}`;
            text.className = `text-xs font-medium ${colors[level-1].class}`;
        }
    </script>
</body>
</html>
