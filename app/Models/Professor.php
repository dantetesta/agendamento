<?php
/**
 * Model Professor - Gerenciamento de Professores
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../../core/Database.php';

class Professor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria novo professor
     */
    public function create($data) {
        $sql = "INSERT INTO professores (nome, email, senha_hash, criado_em) 
                VALUES (:nome, :email, :senha_hash, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':senha_hash' => $data['senha_hash']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Busca professor por ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM professores WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Busca professor por e-mail
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM professores WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        return $stmt->fetch();
    }
    
    /**
     * Verifica se e-mail já existe
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM professores WHERE email = :email";
        
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':email' => $email];
        
        if ($excludeId) {
            $params[':id'] = $excludeId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Atualiza dados do professor
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['nome'])) {
            $fields[] = "nome = :nome";
            $params[':nome'] = $data['nome'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['senha_hash'])) {
            $fields[] = "senha_hash = :senha_hash";
            $params[':senha_hash'] = $data['senha_hash'];
        }
        
        if (isset($data['foto'])) {
            $fields[] = "foto = :foto";
            $params[':foto'] = $data['foto'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE professores SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Atualiza foto do professor
     */
    public function updateFoto($id, $foto) {
        return $this->update($id, ['foto' => $foto]);
    }
    
    /**
     * Cria token de reset de senha
     */
    public function createResetToken($email) {
        $token = generateToken();
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hora
        
        // Primeiro deleta tokens antigos deste e-mail
        $sqlDelete = "DELETE FROM password_resets WHERE email = :email";
        $stmtDelete = $this->db->prepare($sqlDelete);
        $stmtDelete->execute([':email' => $email]);
        
        // Insere novo token
        $sql = "INSERT INTO password_resets (email, token, expiry, criado_em) 
                VALUES (:email, :token, :expiry, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expiry' => $expiry
        ]);
        
        return $token;
    }
    
    /**
     * Valida token de reset
     */
    public function validateResetToken($token) {
        $sql = "SELECT * FROM password_resets 
                WHERE token = :token AND expiry > NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        
        return $stmt->fetch();
    }
    
    /**
     * Deleta token de reset usado
     */
    public function deleteResetToken($token) {
        $sql = "DELETE FROM password_resets WHERE token = :token";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':token' => $token]);
    }
    
    /**
     * Atualiza senha
     */
    public function updatePassword($id, $senhaHash) {
        return $this->update($id, ['senha_hash' => $senhaHash]);
    }
}
