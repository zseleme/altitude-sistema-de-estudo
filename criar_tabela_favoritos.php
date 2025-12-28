<?php
require_once __DIR__ . '/includes/auth.php';

$db = Database::getInstance();

try {
    // Criar tabela de favoritos
    $db->execute("
        CREATE TABLE IF NOT EXISTS cursos_favoritos (
            usuario_id INTEGER NOT NULL,
            curso_id INTEGER NOT NULL,
            data_favoritado DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (usuario_id, curso_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
        )
    ");

    echo "✅ Tabela cursos_favoritos criada com sucesso!<br>";
    echo "Agora você pode voltar para <a href='/cursos.php'>Cursos</a>";

} catch (Exception $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage();
}
?>
