<?php
/**
 * API: Buscar Clientes (Autocomplete)
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 17:52
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../app/Models/Cliente.php';

header('Content-Type: application/json');

// Verifica autenticação
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$professorId = Auth::id();
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$clienteModel = new Cliente();
$clientes = $clienteModel->searchByName($professorId, $query);

echo json_encode($clientes);
