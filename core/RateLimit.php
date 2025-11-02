<?php
/**
 * Classe RateLimit - Proteção contra Força Bruta
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 01/11/2025 21:55
 */

class RateLimit {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../storage/rate_limit/';
        
        // Cria diretório se não existir
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Verifica se atingiu o limite
     * 
     * @param string $key Identificador único (ex: 'login_192.168.1.1')
     * @param int $maxAttempts Máximo de tentativas
     * @param int $timeWindow Janela de tempo em segundos
     * @return bool True se dentro do limite, False se excedeu
     */
    public function check($key, $maxAttempts = 5, $timeWindow = 900) {
        $file = $this->getCacheFile($key);
        $attempts = $this->getAttempts($file);
        
        // Limpa tentativas antigas
        $attempts = $this->cleanOldAttempts($attempts, $timeWindow);
        
        // Verifica se excedeu
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Registra uma tentativa
     */
    public function hit($key) {
        $file = $this->getCacheFile($key);
        $attempts = $this->getAttempts($file);
        
        // Adiciona nova tentativa
        $attempts[] = time();
        
        // Salva
        file_put_contents($file, json_encode($attempts));
    }
    
    /**
     * Reseta o contador
     */
    public function reset($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Obtém tempo restante de bloqueio
     */
    public function getRemainingTime($key, $timeWindow = 900) {
        $file = $this->getCacheFile($key);
        $attempts = $this->getAttempts($file);
        
        if (empty($attempts)) {
            return 0;
        }
        
        $oldestAttempt = min($attempts);
        $elapsed = time() - $oldestAttempt;
        $remaining = $timeWindow - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Verifica e bloqueia se necessário
     */
    public function checkOrBlock($key, $maxAttempts = 5, $timeWindow = 900) {
        if (!$this->check($key, $maxAttempts, $timeWindow)) {
            $remaining = $this->getRemainingTime($key, $timeWindow);
            $minutes = ceil($remaining / 60);
            
            http_response_code(429);
            die("Muitas tentativas. Tente novamente em {$minutes} minuto(s).");
        }
    }
    
    /**
     * Obtém arquivo de cache
     */
    private function getCacheFile($key) {
        $hash = md5($key);
        return $this->cacheDir . $hash . '.json';
    }
    
    /**
     * Obtém tentativas do arquivo
     */
    private function getAttempts($file) {
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        $attempts = json_decode($content, true);
        
        return is_array($attempts) ? $attempts : [];
    }
    
    /**
     * Remove tentativas antigas
     */
    private function cleanOldAttempts($attempts, $timeWindow) {
        $now = time();
        
        return array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
    }
    
    /**
     * Limpa arquivos antigos (garbage collection)
     */
    public function cleanup($olderThan = 86400) {
        $files = glob($this->cacheDir . '*.json');
        $now = time();
        
        foreach ($files as $file) {
            if (($now - filemtime($file)) > $olderThan) {
                unlink($file);
            }
        }
    }
}
