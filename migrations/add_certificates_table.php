<?php
/**
 * Migration: Adiciona tabela de certificados externos
 *
 * Permite aos usuários adicionar certificados de outras plataformas
 * organizados por categoria (Graduação, Pós/MBA, Extensão, Cursos Livres)
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MIGRATION: Adicionando tabela de certificados ===\n\n";

try {
    $db = Database::getInstance();

    if ($db->isSQLite()) {
        echo "📊 Banco de dados: SQLite\n\n";

        // Criar tabela certificados_externos
        $db->execute("
            CREATE TABLE IF NOT EXISTS certificados_externos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                instituicao VARCHAR(255) NOT NULL,
                categoria VARCHAR(50) NOT NULL CHECK (categoria IN ('graduacao', 'pos_mba', 'extensao', 'curso_livre')),
                descricao TEXT,
                data_conclusao DATE,
                carga_horaria INTEGER,
                arquivo_certificado TEXT,
                url_verificacao TEXT,
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )
        ");
        echo "✅ Tabela 'certificados_externos' criada (SQLite)\n";

    } else {
        echo "📊 Banco de dados: PostgreSQL\n\n";

        // Criar ENUM para categoria
        $db->execute("
            DO $$ BEGIN
                CREATE TYPE categoria_certificado AS ENUM ('graduacao', 'pos_mba', 'extensao', 'curso_livre');
            EXCEPTION
                WHEN duplicate_object THEN null;
            END $$;
        ");
        echo "✅ ENUM 'categoria_certificado' criado\n";

        // Criar tabela certificados_externos
        $db->execute("
            CREATE TABLE IF NOT EXISTS certificados_externos (
                id SERIAL PRIMARY KEY,
                usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
                titulo VARCHAR(255) NOT NULL,
                instituicao VARCHAR(255) NOT NULL,
                categoria categoria_certificado NOT NULL,
                descricao TEXT,
                data_conclusao DATE,
                carga_horaria INTEGER,
                arquivo_certificado TEXT,
                url_verificacao TEXT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Tabela 'certificados_externos' criada (PostgreSQL)\n";
    }

    echo "\n✅ Migration executada com sucesso!\n";
    echo "\nTabela criada:\n";
    echo "  • certificados_externos\n\n";
    echo "Campos:\n";
    echo "  • id: Identificador único\n";
    echo "  • usuario_id: Referência ao usuário\n";
    echo "  • titulo: Nome do certificado/curso\n";
    echo "  • instituicao: Nome da instituição/plataforma\n";
    echo "  • categoria: graduacao, pos_mba, extensao, curso_livre\n";
    echo "  • descricao: Descrição opcional\n";
    echo "  • data_conclusao: Data de conclusão\n";
    echo "  • carga_horaria: Carga horária em horas\n";
    echo "  • arquivo_certificado: Path do arquivo PDF/imagem\n";
    echo "  • url_verificacao: URL para verificar autenticidade\n";
    echo "  • data_criacao: Data de cadastro no sistema\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
