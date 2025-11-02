<?php
/**
 * Script para aplicar migration de agendamentos recorrentes
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 16:51
 * 
 * INSTRUÇÕES:
 * 1. Acesse via navegador: /database/migrations/APLICAR_MIGRATION.php
 * 2. Ou execute via CLI: php APLICAR_MIGRATION.php
 */

// Carrega helpers e database
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Database.php';

// Carrega configuração do banco
$dbConfig = require __DIR__ . '/../../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Migration - Agendamentos Recorrentes</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        pre { background: #000; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
<h1>🔄 Migration: Agendamentos Recorrentes</h1>
<hr>
";

try {
    // Obtém conexão usando Singleton
    $db = Database::getInstance()->getConnection();
    
    echo "<p class='info'>📋 Lendo arquivo SQL...</p>";
    $sql = file_get_contents(__DIR__ . '/001_create_agendamentos_series.sql');
    
    // Remove comentários e divide em statements
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt); }
    );
    
    echo "<p class='info'>📊 Total de comandos: " . count($statements) . "</p>";
    echo "<hr>";
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        $num = $index + 1;
        echo "<p class='info'>Executando comando {$num}...</p>";
        
        try {
            $db->exec($statement);
            echo "<p class='success'>✅ Comando {$num} executado com sucesso!</p>";
            $success++;
        } catch (PDOException $e) {
            // Ignora erros de "já existe"
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p class='info'>⚠️ Comando {$num} já foi aplicado anteriormente.</p>";
                $success++;
            } else {
                echo "<p class='error'>❌ Erro no comando {$num}: " . $e->getMessage() . "</p>";
                $errors++;
            }
        }
        
        echo "<hr>";
    }
    
    echo "<h2>📊 Resumo:</h2>";
    echo "<p class='success'>✅ Sucesso: {$success}</p>";
    echo "<p class='error'>❌ Erros: {$errors}</p>";
    
    if ($errors === 0) {
        echo "<h2 class='success'>🎉 MIGRATION APLICADA COM SUCESSO!</h2>";
        echo "<p>Tabela <strong>agendamentos_series</strong> criada!</p>";
        echo "<p>Coluna <strong>serie_id</strong> adicionada em agendamentos!</p>";
        echo "<p>Sistema de agendamentos recorrentes está pronto para uso!</p>";
    } else {
        echo "<h2 class='error'>⚠️ MIGRATION CONCLUÍDA COM ERROS</h2>";
        echo "<p>Verifique os erros acima e corrija antes de continuar.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ ERRO FATAL: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "
<hr>
<p><a href='/dashboard' style='color: #0ff;'>← Voltar para o Dashboard</a></p>
</body>
</html>";
