<?php
/**
 * Model Configuracao - Gerenciamento de Configurações do Professor
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../../core/Database.php';

class Configuracao {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria ou atualiza configuração do professor
     */
    public function save($professorId, $data) {
        // Verifica se já existe configuração
        $existing = $this->getByProfessor($professorId);
        
        if ($existing) {
            return $this->update($professorId, $data);
        } else {
            return $this->create($professorId, $data);
        }
    }
    
    /**
     * Cria nova configuração
     */
    private function create($professorId, $data) {
        $sql = "INSERT INTO configuracoes (professor_id, duracao_aula, intervalo) 
                VALUES (:professor_id, :duracao_aula, :intervalo)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':professor_id' => $professorId,
            ':duracao_aula' => $data['duracao_aula'],
            ':intervalo' => $data['intervalo']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Atualiza configuração existente
     */
    private function update($professorId, $data) {
        $sql = "UPDATE configuracoes 
                SET duracao_aula = :duracao_aula, intervalo = :intervalo 
                WHERE professor_id = :professor_id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':professor_id' => $professorId,
            ':duracao_aula' => $data['duracao_aula'],
            ':intervalo' => $data['intervalo']
        ]);
    }
    
    /**
     * Busca configuração por professor
     */
    public function getByProfessor($professorId) {
        $sql = "SELECT * FROM configuracoes WHERE professor_id = :professor_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':professor_id' => $professorId]);
        
        return $stmt->fetch();
    }
}
