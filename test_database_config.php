<?php
require_once __DIR__ . '/config/database.php';

echo "=== TESTE DE CONFIGURAÇÃO DO BANCO DE DADOS ===\n\n";

echo "🔧 Configuração atual:\n";
echo "   • Tipo de banco: " . DB_TYPE . "\n";

if (DB_TYPE === 'postgresql') {
    echo "   • Host: " . DB_HOST . "\n";
    echo "   • Porta: " . DB_PORT . "\n";
    echo "   • Database: " . DB_NAME . "\n";
    echo "   • Usuário: " . DB_USER . "\n";
    echo "   • Schema: " . DB_SCHEMA . "\n";
} else {
    echo "   • Caminho do arquivo: " . DB_PATH . "\n";
}

echo "\n🔌 Testando conexão...\n";

try {
    $db = Database::getInstance();
    echo "✅ Conexão estabelecida com sucesso!\n";
    
    echo "\n📊 Informações do banco:\n";
    echo "   • Tipo: " . $db->getDbType() . "\n";
    echo "   • É PostgreSQL: " . ($db->isPostgreSQL() ? 'Sim' : 'Não') . "\n";
    echo "   • É SQLite: " . ($db->isSQLite() ? 'Sim' : 'Não') . "\n";
    
    // Testar uma query simples
    if ($db->isPostgreSQL()) {
        $result = $db->fetchOne("SELECT version() as version");
        echo "   • Versão PostgreSQL: " . $result['version'] . "\n";
    } else {
        $result = $db->fetchOne("SELECT sqlite_version() as version");
        echo "   • Versão SQLite: " . $result['version'] . "\n";
    }
    
    // Testar se as tabelas existem
    echo "\n📋 Verificando tabelas...\n";
    $tables = ['usuarios', 'categorias', 'cursos', 'aulas', 'anotacoes', 'comentarios', 'progresso_aulas', 'materiais_complementares'];
    
    foreach ($tables as $table) {
        try {
            if ($db->isPostgreSQL()) {
                $exists = $db->fetchOne("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = ? AND table_name = ?)", [DB_SCHEMA, $table]);
                $tableExists = $exists['exists'];
            } else {
                $exists = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
                $tableExists = $exists !== false;
            }
            
            echo "   • $table: " . ($tableExists ? '✅ Existe' : '❌ Não existe') . "\n";
        } catch (Exception $e) {
            echo "   • $table: ❌ Erro - " . $e->getMessage() . "\n";
        }
    }
    
    // Testar contagem de registros
    echo "\n📈 Contagem de registros:\n";
    foreach ($tables as $table) {
        try {
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM " . $db->getTableName($table))['count'];
            echo "   • $table: $count registros\n";
        } catch (Exception $e) {
            echo "   • $table: ❌ Erro ao contar\n";
        }
    }
    
    echo "\n🎉 Teste concluído com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
    echo "\n💡 Dicas para resolver:\n";
    
    if (DB_TYPE === 'postgresql') {
        echo "   • Verifique se o PostgreSQL está rodando\n";
        echo "   • Confirme as credenciais de conexão\n";
        echo "   • Verifique se o schema 'estudos' existe\n";
        echo "   • Execute o script setup_postgres.php se necessário\n";
    } else {
        echo "   • Verifique se o diretório tem permissão de escrita\n";
        echo "   • Execute o script setup_sqlite.php para criar o banco\n";
        echo "   • Verifique se o SQLite3 está instalado no PHP\n";
    }
    
    exit(1);
}
?>
