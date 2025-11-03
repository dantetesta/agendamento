<?php
/**
 * API - Buscar Clientes (Autocomplete)
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 03/11/2025 07:30
 * 
 * Retorna lista de clientes para autocomplete
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Database.php';

// Verifica autenticação
if (!Auth::check()) {
    echo json_encode([
        'success' => false,
        'error' => 'Não autorizado'
    ]);
    exit;
}

// Pega query
$query = $_GET['q'] ?? '';
$query = trim($query);

// Mínimo 2 caracteres
if (strlen($query) < 2) {
    echo json_encode([
        'success' => true,
        'clientes' => []
    ]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Busca clientes do professor logado
    $sql = "SELECT 
                id,
                nome,
                email,
                telefone
            FROM clientes 
            WHERE professor_id = :professor_id
            AND (
                nome LIKE :query
                OR email LIKE :query
                OR telefone LIKE :query
            )
            ORDER BY nome ASC
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':professor_id' => Auth::id(),
        ':query' => "%{$query}%"
    ]);
    
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clientes' => $clientes,
        'total' => count($clientes)
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar clientes'
    ]);
}
