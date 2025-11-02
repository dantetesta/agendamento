<?php
/**
 * Classe SecurityLogger - Sistema de Logs de Segurança
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 01/11/2025 22:05
 */

class SecurityLogger {
    private $logDir;
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../storage/logs/';
        
        // Cria diretório se não existir
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Registra evento de segurança
     */
    public function log($type, $message, $userId = null, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message,
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'data' => $data,
        ];
        
        // Arquivo do dia
        $filename = $this->logDir . 'security_' . date('Y-m-d') . '.log';
        
        // Formata linha
        $line = sprintf(
            "[%s] [%s] [IP: %s] [User: %s] %s %s\n",
            $timestamp,
            strtoupper($type),
            $ip,
            $userId ?? 'guest',
            $message,
            !empty($data) ? json_encode($data) : ''
        );
        
        // Escreve no arquivo
        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
        
        return true;
    }
    
    /**
     * Log de login bem-sucedido
     */
    public function logLogin($userId, $email) {
        return $this->log('login_success', "Login bem-sucedido", $userId, ['email' => $email]);
    }
    
    /**
     * Log de tentativa de login falha
     */
    public function logLoginFailed($email, $reason = 'senha_incorreta') {
        return $this->log('login_failed', "Tentativa de login falhou: {$reason}", null, ['email' => $email]);
    }
    
    /**
     * Log de registro
     */
    public function logRegister($userId, $email) {
        return $this->log('register', "Nova conta criada", $userId, ['email' => $email]);
    }
    
    /**
     * Log de bloqueio de conta
     */
    public function logAccountLocked($userId, $email, $reason = 'tentativas_excessivas') {
        return $this->log('account_locked', "Conta bloqueada: {$reason}", $userId, ['email' => $email]);
    }
    
    /**
     * Log de desbloqueio de conta
     */
    public function logAccountUnlocked($userId, $email) {
        return $this->log('account_unlocked', "Conta desbloqueada", $userId, ['email' => $email]);
    }
    
    /**
     * Log de reset de senha
     */
    public function logPasswordReset($userId, $email) {
        return $this->log('password_reset', "Senha redefinida", $userId, ['email' => $email]);
    }
    
    /**
     * Log de solicitação de reset
     */
    public function logPasswordResetRequest($email) {
        return $this->log('password_reset_request', "Solicitação de reset de senha", null, ['email' => $email]);
    }
    
    /**
     * Log de exclusão de conta
     */
    public function logAccountDeleted($userId, $email) {
        return $this->log('account_deleted', "Conta excluída pelo usuário", $userId, ['email' => $email]);
    }
    
    /**
     * Log de rate limit atingido
     */
    public function logRateLimitHit($action, $identifier) {
        return $this->log('rate_limit', "Rate limit atingido: {$action}", null, ['identifier' => $identifier]);
    }
    
    /**
     * Log de CSRF inválido
     */
    public function logCSRFViolation($page) {
        return $this->log('csrf_violation', "Token CSRF inválido", null, ['page' => $page]);
    }
    
    /**
     * Log de reCAPTCHA falhou
     */
    public function logRecaptchaFailed($action, $score = null) {
        return $this->log('recaptcha_failed', "reCAPTCHA falhou: {$action}", null, ['score' => $score]);
    }
    
    /**
     * Log de erro crítico
     */
    public function logError($message, $data = []) {
        return $this->log('error', $message, null, $data);
    }
    
    /**
     * Lê logs de um dia específico
     */
    public function readLogs($date = null) {
        $date = $date ?? date('Y-m-d');
        $filename = $this->logDir . 'security_' . $date . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }
        
        return file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    /**
     * Limpa logs antigos (mais de X dias)
     */
    public function cleanup($daysToKeep = 30) {
        $files = glob($this->logDir . 'security_*.log');
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (($now - filemtime($file)) > ($daysToKeep * 86400)) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Obtém estatísticas de segurança
     */
    public function getStats($date = null) {
        $logs = $this->readLogs($date);
        
        $stats = [
            'total' => count($logs),
            'login_success' => 0,
            'login_failed' => 0,
            'register' => 0,
            'account_locked' => 0,
            'rate_limit' => 0,
            'csrf_violation' => 0,
            'recaptcha_failed' => 0,
            'errors' => 0,
        ];
        
        foreach ($logs as $log) {
            if (strpos($log, '[LOGIN_SUCCESS]') !== false) $stats['login_success']++;
            if (strpos($log, '[LOGIN_FAILED]') !== false) $stats['login_failed']++;
            if (strpos($log, '[REGISTER]') !== false) $stats['register']++;
            if (strpos($log, '[ACCOUNT_LOCKED]') !== false) $stats['account_locked']++;
            if (strpos($log, '[RATE_LIMIT]') !== false) $stats['rate_limit']++;
            if (strpos($log, '[CSRF_VIOLATION]') !== false) $stats['csrf_violation']++;
            if (strpos($log, '[RECAPTCHA_FAILED]') !== false) $stats['recaptcha_failed']++;
            if (strpos($log, '[ERROR]') !== false) $stats['errors']++;
        }
        
        return $stats;
    }
}
