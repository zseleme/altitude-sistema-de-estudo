<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$entryId = isset($_GET['entry_id']) ? (int)$_GET['entry_id'] : null;

if (!$entryId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID da entrada não fornecido']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    $entry = $db->fetchOne(
        "SELECT revisao_ia FROM ingles_diario WHERE id = ? AND usuario_id = ?",
        [$entryId, $userId]
    );

    if (!$entry) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Entrada não encontrada']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'review' => $entry['revisao_ia']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar revisão: ' . $e->getMessage()
    ]);
}
?>
