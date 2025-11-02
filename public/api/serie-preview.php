<?php
/**
 * API: Preview de Datas da Série
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 16:51
 * 
 * Retorna as próximas datas que serão geradas pela série
 */

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json');

// Verifica autenticação
if (!isset($_SESSION['professor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

try {
    // Recebe parâmetros
    $tipo = $_GET['tipo'] ?? '';
    $diasSemana = $_GET['dias_semana'] ?? '';
    $intervalo = (int)($_GET['intervalo'] ?? 1);
    $diaMes = (int)($_GET['dia_mes'] ?? 1);
    $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d');
    $dataFim = $_GET['data_fim'] ?? null;
    $maxOcorrencias = (int)($_GET['max_ocorrencias'] ?? 10);
    
    // Limita preview a 10 datas
    $maxOcorrencias = min($maxOcorrencias, 10);
    
    $datas = [];
    $dataAtual = new DateTime($dataInicio);
    $dataLimite = $dataFim ? new DateTime($dataFim) : (clone $dataAtual)->modify('+3 months');
    $contador = 0;
    
    switch ($tipo) {
        case 'diario':
            while ($dataAtual <= $dataLimite && $contador < $maxOcorrencias) {
                $datas[] = [
                    'data' => $dataAtual->format('Y-m-d'),
                    'data_formatada' => strftime('%d/%m/%Y (%A)', $dataAtual->getTimestamp()),
                    'dia_semana' => strftime('%A', $dataAtual->getTimestamp())
                ];
                $dataAtual->modify('+' . $intervalo . ' days');
                $contador++;
            }
            break;
            
        case 'semanal':
            if (empty($diasSemana)) {
                throw new Exception('Selecione os dias da semana');
            }
            
            $dias = explode(',', $diasSemana);
            
            while ($dataAtual <= $dataLimite && $contador < $maxOcorrencias) {
                $diaSemanaAtual = $dataAtual->format('N');
                
                if (in_array($diaSemanaAtual, $dias)) {
                    $datas[] = [
                        'data' => $dataAtual->format('Y-m-d'),
                        'data_formatada' => strftime('%d/%m/%Y (%A)', $dataAtual->getTimestamp()),
                        'dia_semana' => strftime('%A', $dataAtual->getTimestamp())
                    ];
                    $contador++;
                }
                
                $dataAtual->modify('+1 day');
                
                // Pula semanas se intervalo > 1
                if ($diaSemanaAtual == 7 && $intervalo > 1) {
                    $dataAtual->modify('+' . (($intervalo - 1) * 7) . ' days');
                }
            }
            break;
            
        case 'mensal':
            while ($dataAtual <= $dataLimite && $contador < $maxOcorrencias) {
                $dataAgendamento = clone $dataAtual;
                $ultimoDiaMes = (int)$dataAgendamento->format('t');
                $diaEscolhido = min($diaMes, $ultimoDiaMes);
                
                $dataAgendamento->setDate(
                    $dataAgendamento->format('Y'),
                    $dataAgendamento->format('m'),
                    $diaEscolhido
                );
                
                if ($dataAgendamento <= $dataLimite) {
                    $datas[] = [
                        'data' => $dataAgendamento->format('Y-m-d'),
                        'data_formatada' => strftime('%d/%m/%Y (%A)', $dataAgendamento->getTimestamp()),
                        'dia_semana' => strftime('%A', $dataAgendamento->getTimestamp())
                    ];
                    $contador++;
                }
                
                $dataAtual->modify('+' . $intervalo . ' months');
            }
            break;
            
        default:
            throw new Exception('Tipo de recorrência inválido');
    }
    
    echo json_encode([
        'success' => true,
        'datas' => $datas,
        'total' => count($datas),
        'tipo' => $tipo,
        'intervalo' => $intervalo
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
