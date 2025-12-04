<?php
/**
 * Migration: Adiciona tabela para cursos arquivados por usuário
 *
 * Permite que usuários arquivem cursos sem removê-los da listagem principal
 * mas mantendo nas estatísticas
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MIGRATION: Adicionando tabela cursos_arquivados ===\n\n";

try {
    $db = Database::getInstance();

    echo "📝 Criando tabela cursos_arquivados...\n";

    if ($db->isPostgreSQL()) {
        $db->execute("
            CREATE TABLE IF NOT EXISTS cursos_arquivados (
                usuario_id INTEGER NOT NULL,
                curso_id INTEGER NOT NULL,
                data_arquivamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (usuario_id, curso_id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
            )
        ");
    } else {
        $db->execute("
            CREATE TABLE IF NOT EXISTS cursos_arquivados (
                usuario_id INTEGER NOT NULL,
                curso_id INTEGER NOT NULL,
                data_arquivamento DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (usuario_id, curso_id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
            )
        ");
    }

    echo "  ✅ Tabela cursos_arquivados criada com sucesso\n";

    echo "\n✅ Migration executada com sucesso!\n";
    echo "\nEstrutura criada:\n";
    echo "  • cursos_arquivados (usuario_id, curso_id, data_arquivamento)\n";
    echo "  • Permite arquivar cursos por usuário\n";
    echo "  • Cursos arquivados continuam nas estatísticas\n\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
