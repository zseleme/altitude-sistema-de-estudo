<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/security_headers.php';

// Apply minimal security headers for API
SecurityHeaders::applyMinimal();

header('Content-Type: application/json');

// Verificar se está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Validar CSRF token
CSRFHelper::validateRequest();

$aulaId = (int)($_POST['aula_id'] ?? 0);
$conteudo = trim($_POST['conteudo'] ?? '');
$usuarioId = $_SESSION['user_id'];

if (!$aulaId) {
    echo json_encode(['success' => false, 'error' => 'ID da aula não fornecido']);
    exit;
}

if (empty($conteudo)) {
    echo json_encode(['success' => false, 'error' => 'Conteúdo não pode estar vazio']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Verificar se já existe anotação
    $anotacaoExistente = $db->fetchOne(
        "SELECT id FROM anotacoes WHERE usuario_id = ? AND aula_id = ?",
        [$usuarioId, $aulaId]
    );
    
    if ($anotacaoExistente) {
        // Atualizar anotação existente
        $db->execute(
            "UPDATE anotacoes SET conteudo = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE usuario_id = ? AND aula_id = ?",
            [$conteudo, $usuarioId, $aulaId]
        );
    } else {
        // Criar nova anotação
        $db->execute(
            "INSERT INTO anotacoes (usuario_id, aula_id, conteudo) VALUES (?, ?, ?)",
            [$usuarioId, $aulaId, $conteudo]
        );
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Erro ao salvar anotação: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar anotação']);
}
?>