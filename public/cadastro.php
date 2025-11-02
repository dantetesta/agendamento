<?php
/**
 * Redirecionamento - Não deve ser acessado diretamente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:55
 * 
 * Este arquivo não deve ser acessado diretamente.
 * Use as rotas amigáveis: /registro ou /cadastro
 */

require_once __DIR__ . '/../core/Helpers.php';

// Redireciona para página de registro
redirect('/registro');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    
    // Validação
    if (empty($nome)) {
        $errors[] = 'O nome é obrigatório.';
    }
    
    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório.';
    } elseif (!validEmail($email)) {
        $errors[] = 'E-mail inválido.';
    }
    
    if (empty($senha)) {
        $errors[] = 'A senha é obrigatória.';
    } elseif (!validPassword($senha)) {
        $errors[] = 'A senha deve ter no mínimo 6 caracteres.';
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
    
    // Se não houver erros, cria o professor
    if (empty($errors)) {
        try {
            $professorId = $professorModel->create([
                'nome' => $nome,
                'email' => $email,
                'senha_hash' => hashPassword($senha)
            ]);
            
            // Faz login automático
            $professor = $professorModel->findById($professorId);
            Auth::login($professorId, $professor);
            
            setFlash('success', 'Conta criada com sucesso! Bem-vindo(a)!');
            redirect('/public/dashboard.php');
            
        } catch (Exception $e) {
            $errors[] = 'Erro ao criar conta. Tente novamente.';
        }
    }
    
    // Mantém valores preenchidos
    setOld($_POST);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crie sua conta gratuita no Agenda do Professor Inteligente">
    <title>Criar Conta - Agenda do Professor Inteligente</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-md w-full">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <a href="/public/index.php" class="inline-flex items-center text-blue-600 hover:text-blue-700 mb-4">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para home
            </a>
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                <i class="fas fa-user-plus text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Criar Conta</h1>
            <p class="text-gray-600 mt-2">Comece a organizar suas aulas agora</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-red-700 mb-2">Erro ao criar conta:</p>
                            <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= sanitize($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-1"></i>
                        Nome Completo *
                    </label>
                    <input type="text" name="nome" required
                           value="<?= old('nome') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Seu nome completo">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1"></i>
                        E-mail *
                    </label>
                    <input type="email" name="email" required
                           value="<?= old('email') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="seu@email.com">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1"></i>
                        Senha *
                    </label>
                    <input type="password" name="senha" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Mínimo 6 caracteres">
                    <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1"></i>
                        Confirmar Senha *
                    </label>
                    <input type="password" name="confirmar_senha" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Digite a senha novamente">
                </div>
                
                <div class="pt-2">
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                        <i class="fas fa-user-plus mr-2"></i>
                        Criar Minha Conta
                    </button>
                </div>
                
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Já tem uma conta? 
                    <a href="/public/index.php#login" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Faça login
                    </a>
                </p>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-600">
            <p>&copy; <?= date('Y') ?> Agenda do Professor Inteligente</p>
            <p class="mt-1">Por <a href="https://dantetesta.com.br" target="_blank" class="text-blue-600 hover:underline">Dante Testa</a></p>
        </div>
        
    </div>
    
</body>
</html>
