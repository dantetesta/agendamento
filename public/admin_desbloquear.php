<?php
/**
 * Admin - Desbloquear Usuários
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 07:58
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/AccountLock.php';
require_once __DIR__ . '/../app/Models/Professor.php';

// Requer autenticação
Auth::requireAuth();

// Verifica se é admin
$user = Auth::user();
if (!isset($user['is_admin']) || $user['is_admin'] != 1) {
    setFlash('error', 'Acesso negado! Apenas administradores podem acessar esta página.');
    redirect('/dashboard');
    exit;
}

$config = require __DIR__ . '/../config/app.php';
$theme = require __DIR__ . '/../config/theme.php';

$professorModel = new Professor();
$accountLock = new AccountLock();
$message = '';

// Processar desbloqueio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desbloquear'])) {
    $email = sanitize($_POST['email'] ?? '');
    
    if (!empty($email)) {
        $accountLock->unlock($email);
        $message = "✅ Usuário {$email} desbloqueado com sucesso!";
    }
}

// Buscar usuários bloqueados
$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT id, nome, email, failed_attempts, locked_until 
    FROM professores 
    WHERE locked_until IS NOT NULL 
       OR failed_attempts > 0
    ORDER BY locked_until DESC
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desbloquear Usuários - <?= $theme['app_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    
    <div class="min-h-screen p-6">
        <div class="max-w-6xl mx-auto">
            
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            <i class="fas fa-unlock-alt text-blue-600 mr-2"></i>
                            Desbloquear Usuários
                        </h1>
                        <p class="text-gray-600 mt-1">Gerencie bloqueios de conta por tentativas falhas</p>
                    </div>
                    <a href="/dashboard" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar
                    </a>
                </div>
            </div>
            
            <!-- Mensagem -->
            <?php if ($message): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <p class="text-green-700"><?= $message ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Info -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                <div class="flex">
                    <i class="fas fa-info-circle text-blue-500 mr-3 mt-1"></i>
                    <div>
                        <p class="text-blue-700 font-semibold">Como funciona o bloqueio:</p>
                        <ul class="text-blue-600 text-sm mt-2 space-y-1">
                            <li>• Após <strong>5 tentativas falhas</strong> de login, a conta é bloqueada</li>
                            <li>• Bloqueio dura <strong>15 minutos</strong></li>
                            <li>• Desbloqueio automático após o tempo</li>
                            <li>• Você pode desbloquear manualmente aqui</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Usuários -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-users mr-2 text-gray-600"></i>
                        Usuários com Tentativas Falhas ou Bloqueados
                    </h2>
                </div>
                
                <?php if (empty($usuarios)): ?>
                    <div class="p-12 text-center">
                        <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
                        <p class="text-gray-600 text-lg">Nenhum usuário bloqueado ou com tentativas falhas!</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tentativas</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <?php
                                    $isBloqueado = $usuario['locked_until'] && strtotime($usuario['locked_until']) > time();
                                    $tempoRestante = $isBloqueado ? ceil((strtotime($usuario['locked_until']) - time()) / 60) : 0;
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-semibold text-gray-900"><?= sanitize($usuario['nome']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= sanitize($usuario['email']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $usuario['failed_attempts'] >= 5 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                <?= $usuario['failed_attempts'] ?>/5
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($isBloqueado): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-lock mr-1"></i>
                                                    Bloqueado (<?= $tempoRestante ?>min)
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-unlock mr-1"></i>
                                                    Desbloqueado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="email" value="<?= sanitize($usuario['email']) ?>">
                                                <button type="submit" name="desbloquear" 
                                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                                    <i class="fas fa-unlock-alt mr-1"></i>
                                                    Desbloquear
                                                </button>
                                            </form>
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
    
</body>
</html>
