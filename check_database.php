<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "=== VERIFICAÇÃO DO BANCO DE DADOS ===\n\n";
    
    // Verificar conexão
    echo "✅ Conexão com PostgreSQL estabelecida\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "Schema: " . DB_SCHEMA . "\n\n";
    
    // Verificar tabelas
    echo "=== TABELAS CRIADAS ===\n";
    $tables = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'estudos'
        ORDER BY table_name
    ")->fetchAll();
    
    foreach ($tables as $table) {
        echo "✅ " . $table['table_name'] . "\n";
    }
    
    echo "\n=== DADOS INICIAIS ===\n";
    
    // Contar registros
    $usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $categorias = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
    $cursos = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    $aulas = $pdo->query("SELECT COUNT(*) FROM aulas")->fetchColumn();
    
    echo "Usuários: $usuarios\n";
    echo "Categorias: $categorias\n";
    echo "Cursos: $cursos\n";
    echo "Aulas: $aulas\n\n";
    
    // Listar categorias
    echo "=== CATEGORIAS ===\n";
    $categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = TRUE ORDER BY nome")->fetchAll();
    foreach ($categorias as $cat) {
        echo "- " . $cat['nome'] . " (ID: " . $cat['id'] . ")\n";
    }
    
    echo "\n=== CURSOS ===\n";
    $cursos = $pdo->query("
        SELECT c.*, cat.nome as categoria_nome 
        FROM cursos c 
        JOIN categorias cat ON c.categoria_id = cat.id 
        WHERE c.ativo = TRUE 
        ORDER BY cat.nome, c.titulo
    ")->fetchAll();
    
    foreach ($cursos as $curso) {
        echo "- " . $curso['titulo'] . " (" . $curso['categoria_nome'] . ")\n";
    }
    
    echo "\n=== AULAS ===\n";
    $aulas = $pdo->query("
        SELECT a.*, c.titulo as curso_titulo, cat.nome as categoria_nome 
        FROM aulas a 
        JOIN cursos c ON a.curso_id = c.id 
        JOIN categorias cat ON c.categoria_id = cat.id 
        WHERE a.ativo = TRUE 
        ORDER BY cat.nome, c.titulo, a.ordem
    ")->fetchAll();
    
    foreach ($aulas as $aula) {
        echo "- " . $aula['titulo'] . " (" . $aula['curso_titulo'] . " - Aula " . $aula['ordem'] . ")\n";
    }
    
    echo "\n✅ Banco de dados configurado corretamente!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
