<?php
/**
 * API: Criar Série de Agendamentos Recorrentes
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 16:51
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../core/AgendamentoSerie.php';

header('Content-Type: application/json');

// Verifica autenticação
if (!isset($_SESSION['professor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    // Recebe dados
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação básica
    if (empty($input['cliente_id']) || empty($input['horario']) || empty($input['tipo_recorrencia'])) {
        throw new Exception('Dados incompletos');
    }
    
    // Adiciona professor_id da sessão
    $input['professor_id'] = $_SESSION['professor_id'];
    
    // Validações de segurança
    if ($input['tipo_recorrencia'] === 'semanal') {
        if (empty($input['dias_semana'])) {
            throw new Exception('Selecione pelo menos um dia da semana');
        }
        // Valida dias (1-7)
        $dias = explode(',', $input['dias_semana']);
        foreach ($dias as $dia) {
            if ($dia < 1 || $dia > 7) {
                throw new Exception('Dia da semana inválido');
            }
        }
    }
    
    if ($input['tipo_recorrencia'] === 'mensal') {
        if (empty($input['dia_mes']) || $input['dia_mes'] < 1 || $input['dia_mes'] > 31) {
            throw new Exception('Dia do mês inválido');
        }
    }
    
    // Valida intervalo
    if (isset($input['intervalo']) && ($input['intervalo'] < 1 || $input['intervalo'] > 12)) {
        throw new Exception('Intervalo inválido (1-12)');
    }
    
    // Valida datas
    if (empty($input['data_inicio'])) {
        throw new Exception('Data de início é obrigatória');
    }
    
    $dataInicio = new DateTime($input['data_inicio']);
    $hoje = new DateTime();
    $hoje->setTime(0, 0, 0);
    
    if ($dataInicio < $hoje) {
        throw new Exception('Data de início não pode ser no passado');
    }
    
    if (!empty($input['data_fim'])) {
        $dataFim = new DateTime($input['data_fim']);
        if ($dataFim < $dataInicio) {
            throw new Exception('Data fim deve ser posterior à data início');
        }
    }
    
    // Valida max_ocorrencias
    if (isset($input['max_ocorrencias']) && ($input['max_ocorrencias'] < 1 || $input['max_ocorrencias'] > 100)) {
        throw new Exception('Máximo de ocorrências: 1-100');
    }
    
    // Cria a série
    $serie = new AgendamentoSerie();
    $resultado = $serie->criarSerie($input);
    
    if ($resultado['success']) {
        // Log de segurança
        $logger = new SecurityLogger();
        $logger->log(
            $_SESSION['professor_id'],
            'serie_criada',
            "Série #{$resultado['serie_id']} criada: {$resultado['total_gerados']} agendamentos"
        );
        
        http_response_code(201);
        echo json_encode($resultado);
    } else {
        throw new Exception($resultado['error']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
