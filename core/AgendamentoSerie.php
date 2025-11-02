<?php
/**
 * Classe AgendamentoSerie - Gerenciamento de agendamentos recorrentes
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 16:51
 * 
 * Responsável por criar, editar e gerenciar séries de agendamentos recorrentes
 */

class AgendamentoSerie {
    
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    /**
     * Cria uma nova série de agendamentos recorrentes
     * 
     * @param array $dados Dados da série
     * @return array ['success' => bool, 'serie_id' => int, 'total_gerados' => int]
     */
    public function criarSerie($dados) {
        try {
            $this->db->beginTransaction();
            
            // Valida dados obrigatórios
            $this->validarDados($dados);
            
            // Insere a série
            $stmt = $this->db->prepare("
                INSERT INTO agendamentos_series (
                    professor_id, cliente_id, horario, duracao, tag_id, observacoes,
                    tipo_recorrencia, dias_semana, intervalo, dia_mes,
                    data_inicio, data_fim, max_ocorrencias, status
                ) VALUES (
                    :professor_id, :cliente_id, :horario, :duracao, :tag_id, :observacoes,
                    :tipo_recorrencia, :dias_semana, :intervalo, :dia_mes,
                    :data_inicio, :data_fim, :max_ocorrencias, 'ativo'
                )
            ");
            
            $stmt->execute([
                'professor_id' => $dados['professor_id'],
                'cliente_id' => $dados['cliente_id'],
                'horario' => $dados['horario'],
                'duracao' => $dados['duracao'] ?? 60,
                'tag_id' => $dados['tag_id'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
                'tipo_recorrencia' => $dados['tipo_recorrencia'],
                'dias_semana' => $dados['dias_semana'] ?? null,
                'intervalo' => $dados['intervalo'] ?? 1,
                'dia_mes' => $dados['dia_mes'] ?? null,
                'data_inicio' => $dados['data_inicio'],
                'data_fim' => $dados['data_fim'] ?? null,
                'max_ocorrencias' => $dados['max_ocorrencias'] ?? null
            ]);
            
            $serieId = $this->db->lastInsertId();
            
            // Gera os agendamentos da série
            $totalGerados = $this->gerarAgendamentos($serieId);
            
            // Atualiza contador
            $this->db->exec("UPDATE agendamentos_series SET total_gerados = {$totalGerados} WHERE id = {$serieId}");
            
            $this->db->commit();
            
            return [
                'success' => true,
                'serie_id' => $serieId,
                'total_gerados' => $totalGerados,
                'message' => "Série criada com sucesso! {$totalGerados} agendamentos gerados."
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Gera os agendamentos individuais de uma série
     * 
     * @param int $serieId ID da série
     * @return int Total de agendamentos gerados
     */
    public function gerarAgendamentos($serieId) {
        // Busca dados da série
        $stmt = $this->db->prepare("SELECT * FROM agendamentos_series WHERE id = ?");
        $stmt->execute([$serieId]);
        $serie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$serie) {
            throw new Exception("Série não encontrada");
        }
        
        // Gera as datas baseado no tipo de recorrência
        $datas = $this->calcularDatas($serie);
        
        // Cria os agendamentos
        $stmt = $this->db->prepare("
            INSERT INTO agendamentos (
                serie_id, is_recorrente, professor_id, cliente_id, 
                data_agendamento, horario, duracao, tag_id, observacoes, status
            ) VALUES (
                :serie_id, 1, :professor_id, :cliente_id,
                :data_agendamento, :horario, :duracao, :tag_id, :observacoes, 'confirmado'
            )
        ");
        
        $totalGerados = 0;
        foreach ($datas as $data) {
            // Verifica se já existe agendamento nessa data/hora
            if ($this->verificarConflito($serie['professor_id'], $data, $serie['horario'])) {
                continue; // Pula se houver conflito
            }
            
            $stmt->execute([
                'serie_id' => $serieId,
                'professor_id' => $serie['professor_id'],
                'cliente_id' => $serie['cliente_id'],
                'data_agendamento' => $data,
                'horario' => $serie['horario'],
                'duracao' => $serie['duracao'],
                'tag_id' => $serie['tag_id'],
                'observacoes' => $serie['observacoes']
            ]);
            
            $totalGerados++;
        }
        
        return $totalGerados;
    }
    
    /**
     * Calcula as datas dos agendamentos baseado na regra de recorrência
     * 
     * @param array $serie Dados da série
     * @return array Array de datas (Y-m-d)
     */
    private function calcularDatas($serie) {
        $datas = [];
        $dataAtual = new DateTime($serie['data_inicio']);
        $dataFim = $serie['data_fim'] ? new DateTime($serie['data_fim']) : null;
        $maxOcorrencias = $serie['max_ocorrencias'] ?? 100; // Limite padrão
        $contador = 0;
        
        // Limite de segurança: máximo 3 meses à frente se não tiver data fim
        if (!$dataFim) {
            $dataFim = new DateTime($serie['data_inicio']);
            $dataFim->modify('+3 months');
        }
        
        switch ($serie['tipo_recorrencia']) {
            case 'diario':
                while ($dataAtual <= $dataFim && $contador < $maxOcorrencias) {
                    $datas[] = $dataAtual->format('Y-m-d');
                    $dataAtual->modify('+' . $serie['intervalo'] . ' days');
                    $contador++;
                }
                break;
                
            case 'semanal':
                $diasSemana = explode(',', $serie['dias_semana']);
                $semanaAtual = 0;
                
                while ($dataAtual <= $dataFim && $contador < $maxOcorrencias) {
                    $diaSemanaAtual = $dataAtual->format('N'); // 1=segunda, 7=domingo
                    
                    if (in_array($diaSemanaAtual, $diasSemana)) {
                        $datas[] = $dataAtual->format('Y-m-d');
                        $contador++;
                    }
                    
                    $dataAtual->modify('+1 day');
                    
                    // Se chegou no domingo, pula X semanas
                    if ($diaSemanaAtual == 7 && $serie['intervalo'] > 1) {
                        $dataAtual->modify('+' . (($serie['intervalo'] - 1) * 7) . ' days');
                    }
                }
                break;
                
            case 'mensal':
                $diaMes = $serie['dia_mes'] ?? 1;
                
                while ($dataAtual <= $dataFim && $contador < $maxOcorrencias) {
                    // Define o dia do mês
                    $dataAgendamento = clone $dataAtual;
                    $dataAgendamento->setDate(
                        $dataAgendamento->format('Y'),
                        $dataAgendamento->format('m'),
                        min($diaMes, $dataAgendamento->format('t')) // Ajusta se o mês não tiver o dia
                    );
                    
                    if ($dataAgendamento <= $dataFim) {
                        $datas[] = $dataAgendamento->format('Y-m-d');
                        $contador++;
                    }
                    
                    $dataAtual->modify('+' . $serie['intervalo'] . ' months');
                }
                break;
        }
        
        return $datas;
    }
    
    /**
     * Verifica se há conflito de horário
     * 
     * @param int $professorId
     * @param string $data
     * @param string $horario
     * @return bool
     */
    private function verificarConflito($professorId, $data, $horario) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM agendamentos 
            WHERE professor_id = ? 
            AND data_agendamento = ? 
            AND horario = ?
            AND status != 'cancelado'
        ");
        $stmt->execute([$professorId, $data, $horario]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Valida os dados da série
     * 
     * @param array $dados
     * @throws Exception
     */
    private function validarDados($dados) {
        $required = ['professor_id', 'cliente_id', 'horario', 'tipo_recorrencia', 'data_inicio'];
        
        foreach ($required as $field) {
            if (empty($dados[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        // Validações específicas
        if ($dados['tipo_recorrencia'] === 'semanal' && empty($dados['dias_semana'])) {
            throw new Exception("Dias da semana são obrigatórios para recorrência semanal");
        }
        
        if ($dados['tipo_recorrencia'] === 'mensal' && empty($dados['dia_mes'])) {
            throw new Exception("Dia do mês é obrigatório para recorrência mensal");
        }
    }
    
    /**
     * Cancela uma série inteira
     * 
     * @param int $serieId
     * @param bool $cancelarFuturos Se true, cancela apenas futuros. Se false, cancela todos
     * @return array
     */
    public function cancelarSerie($serieId, $cancelarFuturos = true) {
        try {
            $this->db->beginTransaction();
            
            // Atualiza status da série
            $this->db->exec("UPDATE agendamentos_series SET status = 'finalizado' WHERE id = {$serieId}");
            
            // Cancela agendamentos
            if ($cancelarFuturos) {
                $hoje = date('Y-m-d');
                $stmt = $this->db->prepare("
                    UPDATE agendamentos 
                    SET status = 'cancelado' 
                    WHERE serie_id = ? 
                    AND data_agendamento >= ?
                ");
                $stmt->execute([$serieId, $hoje]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE agendamentos 
                    SET status = 'cancelado' 
                    WHERE serie_id = ?
                ");
                $stmt->execute([$serieId]);
            }
            
            $totalCancelados = $stmt->rowCount();
            
            $this->db->commit();
            
            return [
                'success' => true,
                'total_cancelados' => $totalCancelados,
                'message' => "{$totalCancelados} agendamentos cancelados"
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Busca séries ativas de um professor
     * 
     * @param int $professorId
     * @return array
     */
    public function buscarSeriesAtivas($professorId) {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                c.nome as cliente_nome,
                t.nome as tag_nome,
                t.cor as tag_cor,
                t.icone as tag_icone
            FROM agendamentos_series s
            INNER JOIN clientes c ON s.cliente_id = c.id
            LEFT JOIN tags t ON s.tag_id = t.id
            WHERE s.professor_id = ?
            AND s.status = 'ativo'
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
