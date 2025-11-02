<?php
/**
 * Model Cliente - Gerenciamento de Clientes
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 17:52
 */

require_once __DIR__ . '/../../core/Database.php';

class Cliente {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista todos os clientes do professor
     */
    public function findByProfessor($professorId, $status = null) {
        $sql = "SELECT * FROM clientes WHERE professor_id = ?";
        $params = [$professorId];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca cliente por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM clientes 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca cliente por ID e professor (segurança)
     */
    public function findByIdAndProfessor($id, $professorId) {
        $stmt = $this->db->prepare("
            SELECT * FROM clientes 
            WHERE id = ? AND professor_id = ?
        ");
        $stmt->execute([$id, $professorId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca clientes por nome (autocomplete)
     */
    public function searchByName($professorId, $query) {
        $stmt = $this->db->prepare("
            SELECT id, nome, email, telefone 
            FROM clientes 
            WHERE professor_id = ? 
            AND nome LIKE ? 
            AND status = 'ativo'
            ORDER BY nome ASC
            LIMIT 10
        ");
        $stmt->execute([$professorId, "%{$query}%"]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca cliente por nome exato
     */
    public function getByNome($nome, $professorId) {
        $stmt = $this->db->prepare("
            SELECT * FROM clientes 
            WHERE nome = ? AND professor_id = ?
            LIMIT 1
        ");
        $stmt->execute([$nome, $professorId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo cliente
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO clientes (
                professor_id, nome, email, telefone, cpf, observacoes, status, tag_id, foto
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['professor_id'],
            $data['nome'],
            $data['email'] ?? null,
            $data['telefone'] ?? null,
            $data['cpf'] ?? null,
            $data['observacoes'] ?? null,
            $data['status'] ?? 'ativo',
            $data['tag_id'] ?? null,
            $data['foto'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Atualiza cliente
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE clientes SET
                nome = ?,
                email = ?,
                telefone = ?,
                cpf = ?,
                observacoes = ?,
                status = ?,
                tag_id = ?,
                foto = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['nome'],
            $data['email'] ?? null,
            $data['telefone'] ?? null,
            $data['cpf'] ?? null,
            $data['observacoes'] ?? null,
            $data['status'] ?? 'ativo',
            $data['tag_id'] ?? null,
            $data['foto'] ?? null,
            $id
        ]);
    }
    
    /**
     * Deleta cliente (soft delete - inativa)
     */
    public function delete($id) {
        $stmt = $this->db->prepare("
            UPDATE clientes SET status = 'inativo' WHERE id = ?
        ");
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Deleta cliente permanentemente
     */
    public function forceDelete($id) {
        $stmt = $this->db->prepare("DELETE FROM clientes WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Conta clientes do professor
     */
    public function countByProfessor($professorId, $status = null) {
        $sql = "SELECT COUNT(*) as total FROM clientes WHERE professor_id = ?";
        $params = [$professorId];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Busca agendamentos do cliente
     */
    public function getAgendamentos($clienteId) {
        // Primeiro tenta buscar o nome do cliente
        $cliente = $this->findById($clienteId);
        
        if (!$cliente) {
            return [];
        }
        
        // Tenta buscar por cliente_id (se a coluna existir)
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       DATE_FORMAT(a.data, '%d/%m/%Y') as data_formatada,
                       TIME_FORMAT(a.hora_inicio, '%H:%i') as horario_formatado
                FROM agendamentos a
                WHERE a.cliente_id = ?
                ORDER BY a.data DESC, a.hora_inicio DESC
            ");
            $stmt->execute([$clienteId]);
            $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Se encontrou agendamentos, retorna
            if (!empty($agendamentos)) {
                return $agendamentos;
            }
        } catch (PDOException $e) {
            // Coluna cliente_id não existe, ignora
        }
        
        // Fallback: busca pelo nome do aluno
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   DATE_FORMAT(a.data, '%d/%m/%Y') as data_formatada,
                   TIME_FORMAT(a.hora_inicio, '%H:%i') as horario_formatado
            FROM agendamentos a
            WHERE a.aluno = ?
            ORDER BY a.data DESC, a.hora_inicio DESC
        ");
        $stmt->execute([$cliente['nome']]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta agendamentos do cliente
     */
    public function countAgendamentos($clienteId, $status = null) {
        $sql = "SELECT COUNT(*) as total FROM agendamentos WHERE cliente_id = ?";
        $params = [$clienteId];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Verifica se email já existe (para outro cliente)
     */
    public function emailExists($email, $professorId, $excludeId = null) {
        $sql = "SELECT id FROM clientes WHERE email = ? AND professor_id = ?";
        $params = [$email, $professorId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Verifica se CPF já existe (para outro cliente)
     */
    public function cpfExists($cpf, $professorId, $excludeId = null) {
        $sql = "SELECT id FROM clientes WHERE cpf = ? AND professor_id = ?";
        $params = [$cpf, $professorId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    }
}
