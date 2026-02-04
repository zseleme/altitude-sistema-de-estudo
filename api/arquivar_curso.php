<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/error_helper.php';
require_once __DIR__ . '/../includes/security_headers.php';
requireLogin();

// Apply minimal security headers for API
SecurityHeaders::applyMinimal();

header('Content-Type: application/json');

$db = Database::getInstance();
$usuarioId = $_SESSION['user_id'];

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Validar CSRF token
        CSRFHelper::validateRequest();
        // Arquivar curso
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['curso_id'])) {
            throw new Exception("ID do curso não fornecido");
        }

        $cursoId = (int)$input['curso_id'];

        // Verificar se o curso existe
        $curso = $db->fetchOne("SELECT id FROM cursos WHERE id = ? AND ativo = TRUE", [$cursoId]);

        if (!$curso) {
            throw new Exception("Curso não encontrado");
        }

        // Verificar se já está arquivado
        $jaArquivado = $db->fetchOne(
            "SELECT 1 FROM cursos_arquivados WHERE usuario_id = ? AND curso_id = ?",
            [$usuarioId, $cursoId]
        );

        if ($jaArquivado) {
            throw new Exception("Curso já está arquivado");
        }

        // Arquivar o curso
        $db->execute(
            "INSERT INTO cursos_arquivados (usuario_id, curso_id) VALUES (?, ?)",
            [$usuarioId, $cursoId]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Curso arquivado com sucesso'
        ]);

    } elseif ($method === 'DELETE') {
        // Desarquivar curso
        $cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

        if (!$cursoId) {
            throw new Exception("ID do curso não fornecido");
        }

        // Desarquivar o curso
        $result = $db->execute(
            "DELETE FROM cursos_arquivados WHERE usuario_id = ? AND curso_id = ?",
            [$usuarioId, $cursoId]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Curso desarquivado com sucesso'
        ]);

    } else {
        throw new Exception("Método não permitido");
    }

} catch (Exception $e) {
    error_log("Erro em arquivar_curso.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar solicitação. Tente novamente.'
    ]);
}
