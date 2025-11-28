<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_helper.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verificar se a API está configurada
if (!AIHelper::isConfigured()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'A API de IA não está configurada. Entre em contato com o administrador.'
    ]);
    exit;
}

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
$texto = trim($input['texto'] ?? '');
$entryId = !empty($input['entry_id']) ? (int)$input['entry_id'] : null;

if (empty($texto)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Texto não fornecido']);
    exit;
}

try {
    $ai = new AIHelper();
    $result = $ai->reviewEnglishText($texto);

    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    // Se for uma entrada existente, salvar a revisão
    if ($entryId) {
        // Verificar se a entrada pertence ao usuário
        $entry = $db->fetchOne(
            "SELECT id FROM ingles_diario WHERE id = ? AND usuario_id = ?",
            [$entryId, $userId]
        );

        if (!$entry) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Entrada não encontrada ou sem permissão']);
            exit;
        }

        // Adicionar campo de revisão se não existir
        try {
            if ($db->isSQLite()) {
                // Verificar se a coluna existe
                $columns = $db->fetchAll("PRAGMA table_info(ingles_diario)");
                $hasColumn = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'revisao_ia') {
                        $hasColumn = true;
                        break;
                    }
                }

                if (!$hasColumn) {
                    $db->execute("ALTER TABLE ingles_diario ADD COLUMN revisao_ia TEXT");
                    $db->execute("ALTER TABLE ingles_diario ADD COLUMN data_revisao DATETIME");
                }
            } else {
                // PostgreSQL
                $db->execute("
                    ALTER TABLE ingles_diario
                    ADD COLUMN IF NOT EXISTS revisao_ia TEXT,
                    ADD COLUMN IF NOT EXISTS data_revisao TIMESTAMP
                ");
            }
        } catch (Exception $e) {
            // Coluna já existe, ignorar erro
        }

        // Salvar a revisão
        $db->execute(
            "UPDATE ingles_diario SET revisao_ia = ?, data_revisao = CURRENT_TIMESTAMP WHERE id = ?",
            [$result['review'], $entryId]
        );
    }

    echo json_encode([
        'success' => true,
        'review' => $result['review'],
        'tokens_used' => $result['tokens_used']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar revisão: ' . $e->getMessage()
    ]);
}
?>
