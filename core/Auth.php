<?php
/**
 * Classe Auth - Gerenciamento de Autenticação
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 * 
 * Gerencia login, logout e verificação de sessão
 */

class Auth {
    
    /**
     * Inicia a sessão se ainda não estiver iniciada
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/app.php';
            
            session_name($config['session']['name']);
            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path' => $config['session']['path'],
                'secure' => $config['session']['secure'],
                'httponly' => $config['session']['httponly'],
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
    }
    
    /**
     * Realiza login do usuário
     */
    public static function login($professorId, $professorData) {
        self::startSession();
        
        $_SESSION['professor_id'] = $professorId;
        $_SESSION['professor_nome'] = $professorData['nome'];
        $_SESSION['professor_email'] = $professorData['email'];
        $_SESSION['professor_foto'] = $professorData['foto'] ?? null;
        $_SESSION['professor_timezone'] = $professorData['timezone'] ?? 'America/Sao_Paulo';
        $_SESSION['professor_is_admin'] = $professorData['is_admin'] ?? 0;
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Define timezone do PHP
        date_default_timezone_set($_SESSION['professor_timezone']);
        
        // Regenera ID da sessão para segurança
        session_regenerate_id(true);
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    public static function check() {
        self::startSession();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Verifica timeout da sessão
        $config = require __DIR__ . '/../config/app.php';
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > $config['session']['lifetime'])) {
            self::logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Retorna o ID do professor logado
     */
    public static function id() {
        self::startSession();
        return $_SESSION['professor_id'] ?? null;
    }
    
    /**
     * Retorna dados do professor logado
     */
    public static function user() {
        self::startSession();
        
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['professor_id'] ?? null,
            'nome' => $_SESSION['professor_nome'] ?? null,
            'email' => $_SESSION['professor_email'] ?? null,
            'foto' => $_SESSION['professor_foto'] ?? null,
            'is_admin' => $_SESSION['professor_is_admin'] ?? 0
        ];
    }
    
    /**
     * Realiza logout
     */
    public static function logout() {
        self::startSession();
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Requer autenticação (redireciona se não autenticado)
     */
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
    
    /**
     * Redireciona se já estiver autenticado
     */
    public static function requireGuest() {
        if (self::check()) {
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Verifica se usuário é administrador
     */
    public static function isAdmin() {
        $user = self::user();
        return $user && isset($user['is_admin']) && $user['is_admin'] == 1;
    }
    
    /**
     * Requer que usuário seja admin
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            setFlash('error', 'Acesso negado! Apenas administradores podem acessar esta página.');
            redirect('/dashboard');
            exit;
        }
    }
}
