<?php
/**
 * Classe CSRF - Proteção contra Cross-Site Request Forgery
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 01/11/2025 21:55
 */

class CSRF {
    
    /**
     * Gera um token CSRF e armazena na sessão
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Obtém o token CSRF atual
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return self::generateToken();
        }
        
        // Regenera token se expirou (1 hora)
        if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
            return self::generateToken();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida o token CSRF
     */
    public static function validate($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Se não forneceu token, pega do POST
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? '';
        }
        
        // Verifica se existe token na sessão
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Verifica se token expirou (1 hora)
        if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
            return false;
        }
        
        // Compara tokens (timing-safe)
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Renderiza campo hidden com token
     */
    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Valida ou morre com erro
     */
    public static function validateOrDie() {
        if (!self::validate()) {
            http_response_code(403);
            die('Token CSRF inválido ou expirado. Por favor, recarregue a página e tente novamente.');
        }
    }
}
