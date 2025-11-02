<?php
/**
 * Deletar Conta Própria - Agenda Professor
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 01/11/2025 22:05
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/SecurityLogger.php';
require_once __DIR__ . '/../app/Models/Professor.php';
require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Models/Agendamento.php';

// Requer autenticação
Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/perfil');
}

// Validação CSRF
if (!CSRF::validate()) {
    setFlash('error', 'Token de segurança inválido.');
    redirect('/perfil');
}

$senha = $_POST['senha'] ?? '';
$confirmacao = $_POST['confirmacao'] ?? '';

// Validações
if (empty($senha)) {
    setFlash('error', 'Por favor, digite sua senha para confirmar.');
    redirect('/perfil');
}

if ($confirmacao !== 'DELETAR') {
    setFlash('error', 'Digite DELETAR para confirmar a exclusão.');
    redirect('/perfil');
}

// Busca dados do usuário
$professorModel = new Professor();
$professor = $professorModel->findById(Auth::id());

if (!$professor) {
    setFlash('error', 'Usuário não encontrado.');
    redirect('/perfil');
}

// Verifica senha
if (!verifyPassword($senha, $professor['senha_hash'])) {
    setFlash('error', 'Senha incorreta.');
    redirect('/perfil');
}

// Log antes de deletar
$logger = new SecurityLogger();
$logger->logAccountDeleted(Auth::id(), $professor['email']);

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    $professorId = Auth::id();
    
    // 1. Deleta agendamentos
    $stmt = $db->prepare("DELETE FROM agendamentos WHERE professor_id = ?");
    $stmt->execute([$professorId]);
    
    // 2. Deleta clientes
    $stmt = $db->prepare("DELETE FROM clientes WHERE professor_id = ?");
    $stmt->execute([$professorId]);
    
    // 3. Deleta tags
    $stmt = $db->prepare("DELETE FROM tags WHERE professor_id = ?");
    $stmt->execute([$professorId]);
    
    // 4. Deleta foto de perfil se existir
    if (!empty($professor['foto'])) {
        $fotoPath = __DIR__ . str_replace('/public/', '/', $professor['foto']);
        if (file_exists($fotoPath)) {
            unlink($fotoPath);
        }
    }
    
    // 5. Deleta fotos de clientes
    $uploadDir = __DIR__ . '/uploads/clientes/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . 'cliente_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    // 6. Deleta professor
    $stmt = $db->prepare("DELETE FROM professores WHERE id = ?");
    $stmt->execute([$professorId]);
    
    $db->commit();
    
    // Faz logout
    Auth::logout();
    
    setFlash('success', 'Sua conta foi excluída permanentemente. Sentiremos sua falta!');
    redirect('/');
    
} catch (Exception $e) {
    $db->rollBack();
    $logger->logError('Erro ao deletar conta: ' . $e->getMessage(), ['user_id' => Auth::id()]);
    setFlash('error', 'Erro ao excluir conta. Tente novamente.');
    redirect('/perfil');
}
