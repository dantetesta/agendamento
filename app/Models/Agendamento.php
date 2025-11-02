<?php
/**
 * Model Agendamento - Gerenciamento de Agendamentos
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../../core/Database.php';

class Agendamento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria novo agendamento
     */
    public function create($data) {
        $sql = "INSERT INTO agendamentos 
                (professor_id, cliente_id, aluno, descricao, data, hora_inicio, hora_fim, tag_servico_id, criado_em) 
                VALUES (:professor_id, :cliente_id, :aluno, :descricao, :data, :hora_inicio, :hora_fim, :tag_servico_id, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':professor_id' => $data['professor_id'],
            ':cliente_id' => $data['cliente_id'] ?? null,
            ':aluno' => $data['aluno'],
            ':descricao' => $data['descricao'] ?? null,
            ':data' => $data['data'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim'],
            ':tag_servico_id' => $data['tag_servico_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Busca agendamento por ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM agendamentos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Busca todos agendamentos do professor
     */
    public function getByProfessor($professorId, $limit = null) {
        $sql = "SELECT * FROM agendamentos 
                WHERE professor_id = :professor_id 
                ORDER BY data DESC, hora_inicio DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':professor_id', $professorId, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Busca próximos agendamentos
     */
    public function getProximos($professorId, $limit = 5) {
        $sql = "SELECT * FROM agendamentos 
                WHERE professor_id = :professor_id 
                AND CONCAT(data, ' ', hora_inicio) >= NOW()
                ORDER BY data ASC, hora_inicio ASC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':professor_id', $professorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Busca agendamentos por período
     */
    public function getByPeriodo($professorId, $dataInicio, $dataFim) {
        $sql = "SELECT * FROM agendamentos 
                WHERE professor_id = :professor_id 
                AND data BETWEEN :data_inicio AND :data_fim 
                ORDER BY data, hora_inicio";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':professor_id' => $professorId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Verifica se horário está disponível
     */
    public function isHorarioDisponivel($professorId, $data, $horaInicio, $horaFim, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM agendamentos 
                WHERE professor_id = :professor_id 
                AND data = :data 
                AND (
                    (hora_inicio < :hora_fim AND hora_fim > :hora_inicio)
                )";
        
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [
            ':professor_id' => $professorId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim
        ];
        
        if ($excludeId) {
            $params[':id'] = $excludeId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    }
    
    /**
     * Atualiza agendamento
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['aluno'])) {
            $fields[] = "aluno = :aluno";
            $params[':aluno'] = $data['aluno'];
        }
        
        if (isset($data['descricao'])) {
            $fields[] = "descricao = :descricao";
            $params[':descricao'] = $data['descricao'];
        }
        
        if (isset($data['data'])) {
            $fields[] = "data = :data";
            $params[':data'] = $data['data'];
        }
        
        if (isset($data['hora_inicio'])) {
            $fields[] = "hora_inicio = :hora_inicio";
            $params[':hora_inicio'] = $data['hora_inicio'];
        }
        
        if (isset($data['hora_fim'])) {
            $fields[] = "hora_fim = :hora_fim";
            $params[':hora_fim'] = $data['hora_fim'];
        }
        
        if (isset($data['cliente_id'])) {
            $fields[] = "cliente_id = :cliente_id";
            $params[':cliente_id'] = $data['cliente_id'];
        }
        
        if (isset($data['tag_servico_id'])) {
            $fields[] = "tag_servico_id = :tag_servico_id";
            $params[':tag_servico_id'] = $data['tag_servico_id'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE agendamentos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Deleta agendamento
     */
    public function delete($id) {
        $sql = "DELETE FROM agendamentos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Retorna agendamentos para o calendário (formato FullCalendar)
     */
    public function getForCalendar($professorId, $start = null, $end = null) {
        $sql = "SELECT 
                    id,
                    cliente_id,
                    aluno as title,
                    CONCAT(data, ' ', hora_inicio) as start,
                    CONCAT(data, ' ', hora_fim) as end,
                    aluno as description,
                    descricao,
                    tag_servico_id,
                    serie_id,
                    is_recorrente,
                    '#3b82f6' as backgroundColor,
                    '#2563eb' as borderColor
                FROM agendamentos 
                WHERE professor_id = :professor_id";
        
        if ($start && $end) {
            $sql .= " AND data BETWEEN :start AND :end";
        }
        
        $sql .= " ORDER BY data, hora_inicio";
        
        $stmt = $this->db->prepare($sql);
        $params = [':professor_id' => $professorId];
        
        if ($start && $end) {
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
