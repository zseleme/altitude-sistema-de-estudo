<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aulaId = (int)($_POST['aula_id'] ?? 0);
    $concluida = isset($_POST['concluida']) ? ($_POST['concluida'] === 'true' || $_POST['concluida'] === '1') : false;
    $usuarioId = $_SESSION['user_id'];
    
    if (!$aulaId) {
        echo json_encode(['success' => false, 'message' => 'ID da aula é obrigatório']);
        exit;
    }
    
    try {
        // Verificar se já existe progresso para esta aula
        $progresso = $db->fetchOne(
            "SELECT * FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ?",
            [$usuarioId, $aulaId]
        );
        
        if ($progresso) {
            // Atualizar progresso existente
            $db->execute(
                "UPDATE progresso_aulas SET concluida = ?, data_conclusao = ? WHERE usuario_id = ? AND aula_id = ?",
                [$concluida, $concluida ? date('Y-m-d H:i:s') : null, $usuarioId, $aulaId]
            );
        } else {
            // Criar novo progresso
            $db->execute(
                "INSERT INTO progresso_aulas (usuario_id, aula_id, concluida, data_conclusao) VALUES (?, ?, ?, ?)",
                [$usuarioId, $aulaId, $concluida, $concluida ? date('Y-m-d H:i:s') : null]
            );
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $concluida ? 'Aula marcada como concluída!' : 'Aula marcada como não concluída!',
            'concluida' => $concluida,
            'data_conclusao' => $concluida ? date('Y-m-d H:i:s') : null
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar progresso: ' . $e->getMessage()]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $aulaId = (int)($_GET['aula_id'] ?? 0);
    $usuarioId = $_SESSION['user_id'];
    
    if (!$aulaId) {
        echo json_encode(['success' => false, 'message' => 'ID da aula é obrigatório']);
        exit;
    }
    
    try {
        $progresso = $db->fetchOne(
            "SELECT * FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ?",
            [$usuarioId, $aulaId]
        );
        
        echo json_encode([
            'success' => true,
            'concluida' => $progresso ? $progresso['concluida'] : false,
            'data_conclusao' => $progresso ? $progresso['data_conclusao'] : null
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar progresso: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
