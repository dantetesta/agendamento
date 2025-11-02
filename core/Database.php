<?php
/**
 * Classe Database - Gerenciamento de Conexão MySQL
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 * 
 * Singleton para conexão PDO com MySQL
 */

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() {
        $configFile = __DIR__ . '/../config/database.php';
        $configFileProd = __DIR__ . '/../config/database-production.php';
        
        // Tenta usar o arquivo de configuração
        if (file_exists($configFile)) {
            $config = require $configFile;
        } elseif (file_exists($configFileProd)) {
            // Fallback para arquivo de produção
            $config = require $configFileProd;
        } else {
            die('Erro: Arquivo de configuração do banco de dados não encontrado. Renomeie database-production.php para database.php na pasta config.');
        }
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
        } catch (PDOException $e) {
            die('Erro de conexão com o banco de dados: ' . $e->getMessage());
        }
    }
    
    /**
     * Retorna instância única da conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne unserialize
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
