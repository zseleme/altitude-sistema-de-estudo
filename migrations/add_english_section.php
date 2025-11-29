<?php
/**
 * Migration: Adiciona tabelas para a seção de Inglês
 *
 * Cria tabelas para anotações de inglês e diário diário
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MIGRATION: Adicionando tabelas da seção de Inglês ===\n\n";

try {
    $db = Database::getInstance();

    if ($db->isSQLite()) {
        echo "📊 Banco de dados: SQLite\n\n";

        // Tabela de anotações de inglês
        $db->execute("
            CREATE TABLE IF NOT EXISTS ingles_anotacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                titulo VARCHAR(255),
                conteudo TEXT NOT NULL,
                categoria VARCHAR(50) CHECK (categoria IN ('vocabulario', 'gramatica', 'expressoes', 'pronuncia', 'outros')),
                tags TEXT,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )
        ");
        echo "✅ Tabela 'ingles_anotacoes' criada (SQLite)\n";

        // Tabela de diário de inglês
        $db->execute("
            CREATE TABLE IF NOT EXISTS ingles_diario (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                data_entrada DATE NOT NULL,
                conteudo TEXT NOT NULL,
                humor VARCHAR(20) CHECK (humor IN ('otimo', 'bom', 'neutro', 'ruim', 'pessimo')),
                tags TEXT,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                UNIQUE(usuario_id, data_entrada)
            )
        ");
        echo "✅ Tabela 'ingles_diario' criada (SQLite)\n";

    } else {
        echo "📊 Banco de dados: PostgreSQL\n\n";

        // Criar ENUM para categoria de anotações
        $db->execute("
            DO $$ BEGIN
                CREATE TYPE categoria_ingles AS ENUM ('vocabulario', 'gramatica', 'expressoes', 'pronuncia', 'outros');
            EXCEPTION
                WHEN duplicate_object THEN null;
            END $$;
        ");
        echo "✅ ENUM 'categoria_ingles' criado\n";

        // Criar ENUM para humor
        $db->execute("
            DO $$ BEGIN
                CREATE TYPE humor_diario AS ENUM ('otimo', 'bom', 'neutro', 'ruim', 'pessimo');
            EXCEPTION
                WHEN duplicate_object THEN null;
            END $$;
        ");
        echo "✅ ENUM 'humor_diario' criado\n";

        // Tabela de anotações de inglês
        $db->execute("
            CREATE TABLE IF NOT EXISTS ingles_anotacoes (
                id SERIAL PRIMARY KEY,
                usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
                titulo VARCHAR(255),
                conteudo TEXT NOT NULL,
                categoria categoria_ingles,
                tags TEXT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Tabela 'ingles_anotacoes' criada (PostgreSQL)\n";

        // Tabela de diário de inglês
        $db->execute("
            CREATE TABLE IF NOT EXISTS ingles_diario (
                id SERIAL PRIMARY KEY,
                usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
                data_entrada DATE NOT NULL,
                conteudo TEXT NOT NULL,
                humor humor_diario,
                tags TEXT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(usuario_id, data_entrada)
            )
        ");
        echo "✅ Tabela 'ingles_diario' criada (PostgreSQL)\n";
    }

    echo "\n✅ Migration executada com sucesso!\n";
    echo "\nTabelas criadas:\n";
    echo "  • ingles_anotacoes - Anotações de estudo de inglês\n";
    echo "  • ingles_diario - Diário diário em inglês\n\n";

    echo "Campos de ingles_anotacoes:\n";
    echo "  • titulo: Título da anotação\n";
    echo "  • conteudo: Conteúdo da anotação\n";
    echo "  • categoria: vocabulario, gramatica, expressoes, pronuncia, outros\n";
    echo "  • tags: Tags separadas por vírgula\n\n";

    echo "Campos de ingles_diario:\n";
    echo "  • data_entrada: Data do registro (única por usuário)\n";
    echo "  • conteudo: Texto do diário em inglês\n";
    echo "  • humor: otimo, bom, neutro, ruim, pessimo\n";
    echo "  • tags: Tags separadas por vírgula\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
