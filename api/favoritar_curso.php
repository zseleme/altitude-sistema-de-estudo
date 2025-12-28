<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$usuarioId = $_SESSION['user_id'];

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Adicionar curso aos favoritos
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['curso_id'])) {
            throw new Exception("ID do curso não fornecido");
        }

        $cursoId = (int)$input['curso_id'];

        // Verificar se o curso existe e está ativo
        $curso = $db->fetchOne("SELECT id FROM cursos WHERE id = ? AND ativo = TRUE", [$cursoId]);

        if (!$curso) {
            throw new Exception("Curso não encontrado");
        }

        // Verificar se já está favoritado
        $jaFavoritado = $db->fetchOne(
            "SELECT 1 FROM cursos_favoritos WHERE usuario_id = ? AND curso_id = ?",
            [$usuarioId, $cursoId]
        );

        if ($jaFavoritado) {
            throw new Exception("Curso já está nos favoritos");
        }

        // Adicionar aos favoritos
        $db->execute(
            "INSERT INTO cursos_favoritos (usuario_id, curso_id) VALUES (?, ?)",
            [$usuarioId, $cursoId]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Curso adicionado aos favoritos'
        ]);

    } elseif ($method === 'DELETE') {
        // Remover curso dos favoritos
        $cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

        if (!$cursoId) {
            throw new Exception("ID do curso não fornecido");
        }

        // Remover dos favoritos
        $result = $db->execute(
            "DELETE FROM cursos_favoritos WHERE usuario_id = ? AND curso_id = ?",
            [$usuarioId, $cursoId]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Curso removido dos favoritos'
        ]);

    } else {
        throw new Exception("Método não permitido");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
