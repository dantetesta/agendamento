<?php
/**
 * API de Eventos para FullCalendar - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

// Debug temporário
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/Agendamento.php';
require_once __DIR__ . '/../../app/Models/Cliente.php';
require_once __DIR__ . '/../../app/Models/Tag.php';

// Requer autenticação
if (!Auth::check()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Parâmetros do FullCalendar
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$agendamentoModel = new Agendamento();
$clienteModel = new Cliente();
$tagModel = new Tag();

// Converte datas para formato MySQL
$startDate = $start ? date('Y-m-d', strtotime($start)) : null;
$endDate = $end ? date('Y-m-d', strtotime($end)) : null;

// Debug
error_log("API Eventos - User ID: " . Auth::id());
error_log("API Eventos - Start: " . $startDate);
error_log("API Eventos - End: " . $endDate);

// Busca eventos
$eventos = $agendamentoModel->getForCalendar(Auth::id(), $startDate, $endDate);

// Sistema de Tags Duplo: Tag do Cliente + Tag do Serviço
foreach ($eventos as &$evento) {
    // Cor padrão azul se não tiver tag
    $corPadrao = '#3B82F6';
    
    // ===== TAG DO CLIENTE (QUEM é) =====
    $tagCliente = null;
    $fotoCliente = null;
    $clienteIdParaTags = null;
    $nomeCliente = $evento['title'] ?? '';
    
    // 1º: Tenta usar cliente_id diretamente
    if (!empty($evento['cliente_id'])) {
        $clienteIdParaTags = $evento['cliente_id'];
    } 
    // 2º: Se não tiver cliente_id, busca pelo nome
    else if (!empty($nomeCliente)) {
        $clientePorNome = $clienteModel->getByNome($nomeCliente, Auth::id());
        if (!empty($clientePorNome)) {
            $clienteIdParaTags = $clientePorNome['id'];
        }
    }
    
    // Busca tag e foto do cliente
    if ($clienteIdParaTags) {
        // Busca dados completos do cliente
        $clienteCompleto = $clienteModel->findById($clienteIdParaTags);
        if ($clienteCompleto) {
            $fotoCliente = $clienteCompleto['foto'] ?? null;
        }
        
        // Busca tag do cliente
        $tagsCliente = $tagModel->getByCliente($clienteIdParaTags);
        if (!empty($tagsCliente)) {
            $tagCliente = $tagsCliente[0]; // Pega a tag única
        }
    }
    
    // ===== TAG DO SERVIÇO (O QUÊ fazer) =====
    $tagServico = null;
    if (!empty($evento['tag_servico_id'])) {
        $tagServico = $tagModel->getByIdSimples($evento['tag_servico_id']);
    }
    
    // ===== COR DO EVENTO (usa cor da tag do cliente) =====
    if ($tagCliente) {
        $evento['color'] = $tagCliente['cor'];
        $evento['backgroundColor'] = $tagCliente['cor'];
        $evento['borderColor'] = $tagCliente['cor'];
    } else {
        $evento['color'] = $corPadrao;
        $evento['backgroundColor'] = $corPadrao;
        $evento['borderColor'] = $corPadrao;
    }
    
    // ===== EXTENDED PROPS (dados para o modal) =====
    $evento['extendedProps'] = [
        'descricao' => $evento['descricao'] ?? '',
        'tagCliente' => $tagCliente,  // Tag do cliente (Aluno, Paciente, etc)
        'tagServico' => $tagServico,  // Tag do serviço (Mentoria, Consultoria, etc)
        'fotoCliente' => $fotoCliente // Foto do cliente (300x300px)
    ];
    
    // Remove campos que não são necessários no retorno
    unset($evento['descricao']);
    unset($evento['cliente_id']);
    unset($evento['tag_servico_id']);
}

// Debug
error_log("API Eventos - Total encontrado: " . count($eventos));
error_log("API Eventos - Dados: " . json_encode($eventos));

// Retorna JSON
header('Content-Type: application/json');
echo json_encode($eventos);
exit;
