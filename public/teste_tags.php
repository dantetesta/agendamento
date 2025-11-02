<?php
/**
 * Script de Teste - Verificação da Tabela Tags
 * Autor: Dante Testa (https://dantetesta.com.br)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../core/Database.php';

echo "<h1>Teste da Tabela Tags</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Conexão com banco OK</p>";
    
    // Verifica se a tabela existe
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    $existe = $stmt->fetch();
    
    if ($existe) {
        echo "<p>✅ Tabela 'tags' existe</p>";
        
        // Mostra estrutura da tabela
        echo "<h2>Estrutura da Tabela:</h2>";
        $stmt = $db->query("DESCRIBE tags");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
        foreach ($colunas as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Conta registros
        $stmt = $db->query("SELECT COUNT(*) as total FROM tags");
        $total = $stmt->fetch()['total'];
        echo "<p>📊 Total de tags: <strong>{$total}</strong></p>";
        
        // Lista tags
        if ($total > 0) {
            echo "<h2>Tags Cadastradas:</h2>";
            $stmt = $db->query("SELECT * FROM tags ORDER BY id");
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Cor</th><th>Ícone</th><th>Descrição</th></tr>";
            foreach ($tags as $tag) {
                echo "<tr>";
                echo "<td>{$tag['id']}</td>";
                echo "<td>{$tag['nome']}</td>";
                echo "<td style='background:{$tag['cor']}'>{$tag['cor']}</td>";
                echo "<td><i class='fas {$tag['icone']}'></i> {$tag['icone']}</td>";
                echo "<td>{$tag['descricao']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Teste de INSERT
        echo "<h2>Teste de INSERT:</h2>";
        try {
            $stmt = $db->prepare("
                INSERT INTO tags (nome, cor, icone, descricao) 
                VALUES (?, ?, ?, ?)
            ");
            $result = $stmt->execute(['Teste', '#FF0000', 'fa-test', 'Tag de teste']);
            
            if ($result) {
                $lastId = $db->lastInsertId();
                echo "<p>✅ INSERT funcionou! ID inserido: {$lastId}</p>";
                
                // Remove o teste
                $db->prepare("DELETE FROM tags WHERE id = ?")->execute([$lastId]);
                echo "<p>✅ DELETE de teste funcionou!</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Erro no INSERT: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ Tabela 'tags' NÃO existe!</p>";
        echo "<p>Execute o SQL de criação da tabela.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ ERRO: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='/tags'>← Voltar para Tags</a></p>";
