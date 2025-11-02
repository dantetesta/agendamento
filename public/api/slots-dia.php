<?php
/**
 * API: Slots do Dia (Ocupados e Livres)
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 16:01
 * 
 * Retorna todos os slots de horário do dia (ocupados e livres)
 * para visualização em timeline/agenda
 */

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json');

// Verifica autenticação
if (!isset($_SESSION['professor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$professorId = $_SESSION['professor_id'];
$data = $_GET['data'] ?? date('Y-m-d');

try {
    $db = getConnection();
    
    // Configuração de horários (pode vir do banco depois)
    $horaInicio = 8;  // 08:00
    $horaFim = 18;    // 18:00
    $intervalo = 60;  // 60 minutos
    
    // Busca agendamentos do dia
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.data_agendamento,
            a.horario,
            a.duracao,
            a.observacoes,
            c.nome as cliente_nome,
            c.cor as cliente_cor,
            t.nome as tag_nome,
            t.cor as tag_cor,
            t.icone as tag_icone
        FROM agendamentos a
        INNER JOIN clientes c ON a.cliente_id = c.id
        LEFT JOIN tags t ON a.tag_id = t.id
        WHERE a.professor_id = ?
        AND a.data_agendamento = ?
        AND a.status = 'confirmado'
        ORDER BY a.horario
    ");
    
    $stmt->execute([$professorId, $data]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cria array de horários ocupados
    $horariosOcupados = [];
    foreach ($agendamentos as $ag) {
        $horariosOcupados[$ag['horario']] = $ag;
    }
    
    // Gera todos os slots do dia
    $slots = [];
    $horaAtual = $horaInicio;
    
    while ($horaAtual < $horaFim) {
        $horario = sprintf('%02d:00', $horaAtual);
        
        if (isset($horariosOcupados[$horario])) {
            // Slot ocupado
            $ag = $horariosOcupados[$horario];
            $slots[] = [
                'horario' => $horario,
                'status' => 'ocupado',
                'agendamento' => [
                    'id' => $ag['id'],
                    'cliente' => $ag['cliente_nome'],
                    'cliente_cor' => $ag['cliente_cor'],
                    'tag' => $ag['tag_nome'],
                    'tag_cor' => $ag['tag_cor'],
                    'tag_icone' => $ag['tag_icone'],
                    'observacoes' => $ag['observacoes'],
                    'duracao' => $ag['duracao']
                ]
            ];
        } else {
            // Slot livre
            $slots[] = [
                'horario' => $horario,
                'status' => 'livre',
                'agendamento' => null
            ];
        }
        
        $horaAtual++;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'slots' => $slots,
        'total_slots' => count($slots),
        'slots_ocupados' => count($horariosOcupados),
        'slots_livres' => count($slots) - count($horariosOcupados)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar slots',
        'message' => $e->getMessage()
    ]);
}
