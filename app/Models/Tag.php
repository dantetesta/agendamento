<?php
/**
 * Model Tag - Gerenciamento de Tags/Categorias
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 31/10/2025 16:08
 */

require_once __DIR__ . '/../../core/Database.php';

class Tag {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista todas as tags
     */
    public function getAll() {
        $stmt = $this->db->query("
            SELECT t.*, 
                   COUNT(c.id) as total_clientes
            FROM tags t
            LEFT JOIN clientes c ON t.id = c.tag_id
            GROUP BY t.id
            ORDER BY t.nome ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Busca tag por ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Cria nova tag
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO tags (nome, cor, icone, descricao, categoria) 
            VALUES (:nome, :cor, :icone, :descricao, :categoria)
        ");
        
        return $stmt->execute([
            'nome' => $data['nome'],
            'cor' => $data['cor'] ?? '#3B82F6',
            'icone' => $data['icone'] ?? 'fa-tag',
            'descricao' => $data['descricao'] ?? null,
            'categoria' => $data['categoria'] ?? 'cliente'
        ]);
    }
    
    /**
     * Atualiza tag
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE tags 
            SET nome = :nome, 
                cor = :cor, 
                icone = :icone, 
                descricao = :descricao,
                categoria = :categoria
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $id,
            'nome' => $data['nome'],
            'cor' => $data['cor'],
            'icone' => $data['icone'],
            'descricao' => $data['descricao'] ?? null,
            'categoria' => $data['categoria'] ?? 'cliente'
        ]);
    }
    
    /**
     * Deleta tag
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Busca tags de um cliente (sistema novo - tag única)
     */
    public function getByCliente($clienteId) {
        $stmt = $this->db->prepare("
            SELECT t.* 
            FROM tags t
            INNER JOIN clientes c ON t.id = c.tag_id
            WHERE c.id = ?
        ");
        $stmt->execute([$clienteId]);
        $tag = $stmt->fetch();
        return $tag ? [$tag] : []; // Retorna array para compatibilidade
    }
    
    /**
     * Busca tags por categoria (cliente ou servico)
     */
    public function getByCategoria($categoria = 'cliente') {
        $stmt = $this->db->prepare("
            SELECT * FROM tags 
            WHERE categoria = ?
            ORDER BY nome ASC
        ");
        $stmt->execute([$categoria]);
        return $stmt->fetchAll();
    }
    
    /**
     * Busca tag simples por ID (sem joins)
     */
    public function getByIdSimples($id) {
        if (empty($id)) return null;
        
        $stmt = $this->db->prepare("SELECT * FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Define tag de um cliente (sistema novo - tag única)
     */
    public function setTagCliente($clienteId, $tagId) {
        $stmt = $this->db->prepare("
            UPDATE clientes 
            SET tag_id = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$tagId, $clienteId]);
    }
    
    /**
     * Remove tag de um cliente (sistema novo)
     */
    public function removeTagCliente($clienteId) {
        $stmt = $this->db->prepare("
            UPDATE clientes 
            SET tag_id = NULL 
            WHERE id = ?
        ");
        return $stmt->execute([$clienteId]);
    }
    
    /**
     * DEPRECATED: Use setTagCliente() - Mantido para compatibilidade
     */
    public function syncClienteTags($clienteId, $tagIds) {
        // No sistema novo, pega apenas a primeira tag
        $tagId = !empty($tagIds) ? (is_array($tagIds) ? $tagIds[0] : $tagIds) : null;
        return $this->setTagCliente($clienteId, $tagId);
    }
    
    /**
     * Busca clientes por tag (sistema novo)
     */
    public function getClientesByTag($tagId) {
        $stmt = $this->db->prepare("
            SELECT c.* 
            FROM clientes c
            WHERE c.tag_id = ?
            ORDER BY c.nome ASC
        ");
        $stmt->execute([$tagId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Estatísticas de tags
     */
    public function getStats() {
        $stmt = $this->db->query("
            SELECT 
                t.id,
                t.nome,
                t.cor,
                t.icone,
                t.categoria,
                COUNT(c.id) as total_clientes
            FROM tags t
            LEFT JOIN clientes c ON t.id = c.tag_id
            GROUP BY t.id
            ORDER BY total_clientes DESC, t.nome ASC
        ");
        return $stmt->fetchAll();
    }
}
