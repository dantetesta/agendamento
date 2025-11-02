<?php
/**
 * Classe Router - Sistema de Rotas Amigáveis
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:40
 */

class Router {
    private static $routes = [];
    
    /**
     * Adiciona rota GET
     */
    public static function get($path, $file) {
        self::$routes['GET'][$path] = $file;
    }
    
    /**
     * Adiciona rota POST
     */
    public static function post($path, $file) {
        self::$routes['POST'][$path] = $file;
    }
    
    /**
     * Adiciona rota para qualquer método
     */
    public static function any($path, $file) {
        self::$routes['GET'][$path] = $file;
        self::$routes['POST'][$path] = $file;
    }
    
    /**
     * Processa requisição
     */
    public static function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Busca rota exata
        if (isset(self::$routes[$method][$uri])) {
            return self::loadFile(self::$routes[$method][$uri]);
        }
        
        // Busca rota com parâmetros
        foreach (self::$routes[$method] ?? [] as $route => $file) {
            $pattern = self::routeToRegex($route);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove match completo
                $_GET = array_merge($_GET, $matches);
                return self::loadFile($file);
            }
        }
        
        // Rota não encontrada
        http_response_code(404);
        if (file_exists(__DIR__ . '/../public/404.php')) {
            require __DIR__ . '/../public/404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
        }
        exit;
    }
    
    /**
     * Converte rota para regex
     */
    private static function routeToRegex($route) {
        // Substitui :param por regex
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Carrega arquivo da rota
     */
    private static function loadFile($file) {
        $fullPath = __DIR__ . '/../' . $file;
        
        if (!file_exists($fullPath)) {
            http_response_code(500);
            die("Erro: Arquivo de rota não encontrado: {$file}");
        }
        
        require $fullPath;
        exit;
    }
    
    /**
     * Gera URL amigável
     */
    public static function url($path) {
        $baseUrl = rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Redireciona para URL
     */
    public static function redirect($path, $code = 302) {
        header('Location: ' . self::url($path), true, $code);
        exit;
    }
}
