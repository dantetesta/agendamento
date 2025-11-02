<?php
/**
 * Model Disponibilidade - Gerenciamento de Disponibilidades
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../../core/Database.php';

class Disponibilidade {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria nova disponibilidade
     */
    public function create($data) {
        $sql = "INSERT INTO disponibilidades (professor_id, dia_semana, hora_inicio, hora_fim) 
                VALUES (:professor_id, :dia_semana, :hora_inicio, :hora_fim)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':professor_id' => $data['professor_id'],
            ':dia_semana' => $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Busca todas disponibilidades do professor
     */
    public function getByProfessor($professorId) {
        $sql = "SELECT * FROM disponibilidades 
                WHERE professor_id = :professor_id 
                ORDER BY dia_semana, hora_inicio";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':professor_id' => $professorId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Busca disponibilidades por dia da semana
     */
    public function getByDiaSemana($professorId, $diaSemana) {
        $sql = "SELECT * FROM disponibilidades 
                WHERE professor_id = :professor_id AND dia_semana = :dia_semana 
                ORDER BY hora_inicio";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':professor_id' => $professorId,
            ':dia_semana' => $diaSemana
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Deleta disponibilidade
     */
    public function delete($id) {
        $sql = "DELETE FROM disponibilidades WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Deleta todas disponibilidades do professor
     */
    public function deleteByProfessor($professorId) {
        $sql = "DELETE FROM disponibilidades WHERE professor_id = :professor_id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':professor_id' => $professorId]);
    }
    
    /**
     * Atualiza disponibilidade
     */
    public function update($id, $data) {
        $sql = "UPDATE disponibilidades 
                SET dia_semana = :dia_semana, 
                    hora_inicio = :hora_inicio, 
                    hora_fim = :hora_fim 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':id' => $id,
            ':dia_semana' => $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim']
        ]);
    }
}
