<?php
/**
 * Classe AccountLock - Bloqueio de Conta por Tentativas Falhas
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 01/11/2025 22:05
 */

class AccountLock {
    private $db;
    private $maxAttempts = 5;
    private $lockDuration = 900; // 15 minutos
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Verifica se conta está bloqueada
     */
    public function isLocked($email) {
        $stmt = $this->db->prepare("
            SELECT locked_until, failed_attempts 
            FROM professores 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Se tem locked_until e ainda não expirou
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return true;
        }
        
        // Se expirou, desbloqueia automaticamente
        if ($user['locked_until'] && strtotime($user['locked_until']) <= time()) {
            $this->unlock($email);
            return false;
        }
        
        return false;
    }
    
    /**
     * Registra tentativa falha
     */
    public function recordFailedAttempt($email) {
        // Busca usuário
        $stmt = $this->db->prepare("
            SELECT id, email, failed_attempts 
            FROM professores 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        $attempts = ($user['failed_attempts'] ?? 0) + 1;
        
        // Se atingiu o máximo, bloqueia
        if ($attempts >= $this->maxAttempts) {
            $this->lock($email);
            return true; // Retorna true indicando que foi bloqueado
        }
        
        // Incrementa contador
        $stmt = $this->db->prepare("
            UPDATE professores 
            SET failed_attempts = ? 
            WHERE email = ?
        ");
        $stmt->execute([$attempts, $email]);
        
        return false;
    }
    
    /**
     * Bloqueia conta
     */
    public function lock($email) {
        $lockedUntil = date('Y-m-d H:i:s', time() + $this->lockDuration);
        
        $stmt = $this->db->prepare("
            UPDATE professores 
            SET locked_until = ?, 
                failed_attempts = ? 
            WHERE email = ?
        ");
        $stmt->execute([$lockedUntil, $this->maxAttempts, $email]);
        
        // Log
        $logger = new SecurityLogger();
        $logger->logAccountLocked(null, $email, 'tentativas_excessivas');
        
        return true;
    }
    
    /**
     * Desbloqueia conta
     */
    public function unlock($email) {
        $stmt = $this->db->prepare("
            UPDATE professores 
            SET locked_until = NULL, 
                failed_attempts = 0 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        // Log
        $logger = new SecurityLogger();
        $logger->logAccountUnlocked(null, $email);
        
        return true;
    }
    
    /**
     * Reseta contador de tentativas (após login bem-sucedido)
     */
    public function resetAttempts($email) {
        $stmt = $this->db->prepare("
            UPDATE professores 
            SET failed_attempts = 0, 
                locked_until = NULL 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        return true;
    }
    
    /**
     * Obtém tempo restante de bloqueio
     */
    public function getRemainingTime($email) {
        $stmt = $this->db->prepare("
            SELECT locked_until 
            FROM professores 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$user['locked_until']) {
            return 0;
        }
        
        $remaining = strtotime($user['locked_until']) - time();
        return max(0, $remaining);
    }
    
    /**
     * Obtém número de tentativas falhas
     */
    public function getFailedAttempts($email) {
        $stmt = $this->db->prepare("
            SELECT failed_attempts 
            FROM professores 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user['failed_attempts'] ?? 0;
    }
}
