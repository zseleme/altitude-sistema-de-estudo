<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/error_helper.php';
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    CSRFHelper::validateRequest();
    $aulaId = (int)($_POST['aula_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = $_POST['tipo'] ?? 'outro';
    $urlArquivo = trim($_POST['url_arquivo'] ?? '');
    $nomeArquivo = trim($_POST['nome_arquivo'] ?? '');
    $tamanhoArquivo = (int)($_POST['tamanho_arquivo'] ?? 0);
    $ordem = (int)($_POST['ordem'] ?? 1);
    
    if (!$aulaId || !$titulo) {
        echo json_encode(['success' => false, 'message' => 'Aula e título são obrigatórios']);
        exit;
    }
    
    // Verificar se o usuário tem permissão (admin ou dono da aula)
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit;
    }
    
    try {
        $db->execute(
            "INSERT INTO materiais_complementares (aula_id, titulo, descricao, tipo, url_arquivo, nome_arquivo, tamanho_arquivo, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)",
            [$aulaId, $titulo, $descricao, $tipo, $urlArquivo, $nomeArquivo, $tamanhoArquivo, $ordem]
        );
        
        echo json_encode([
            'success' => true, 
            'message' => 'Material adicionado com sucesso!'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao adicionar material: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar material. Tente novamente.']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Validar CSRF token
    CSRFHelper::validateRequest();

    // Atualizar material - usar JSON decode em vez de parse_str
    $putData = json_decode(file_get_contents('php://input'), true);

    if (!is_array($putData)) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }

    $materialId = (int)($putData['id'] ?? 0);
    $titulo = trim($putData['titulo'] ?? '');
    $descricao = trim($putData['descricao'] ?? '');
    $tipo = $putData['tipo'] ?? 'outro';
    $urlArquivo = trim($putData['url_arquivo'] ?? '');
    $nomeArquivo = trim($putData['nome_arquivo'] ?? '');
    $tamanhoArquivo = (int)($putData['tamanho_arquivo'] ?? 0);
    $ordem = (int)($putData['ordem'] ?? 1);
    
    if (!$materialId || !$titulo) {
        echo json_encode(['success' => false, 'message' => 'ID e título são obrigatórios']);
        exit;
    }
    
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit;
    }
    
    try {
        $db->execute(
            "UPDATE materiais_complementares SET titulo = ?, descricao = ?, tipo = ?, url_arquivo = ?, nome_arquivo = ?, tamanho_arquivo = ?, ordem = ? WHERE id = ?",
            [$titulo, $descricao, $tipo, $urlArquivo, $nomeArquivo, $tamanhoArquivo, $ordem, $materialId]
        );
        
        echo json_encode([
            'success' => true, 
            'message' => 'Material atualizado com sucesso!'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao atualizar material: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar material. Tente novamente.']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Validar CSRF token
    CSRFHelper::validateRequest();

    $materialId = (int)($_GET['id'] ?? 0);
    
    if (!$materialId) {
        echo json_encode(['success' => false, 'message' => 'ID do material é obrigatório']);
        exit;
    }
    
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit;
    }
    
    try {
        $db->execute("UPDATE materiais_complementares SET ativo = FALSE WHERE id = ?", [$materialId]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Material removido com sucesso!'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao remover material: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao remover material. Tente novamente.']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $aulaId = (int)($_GET['aula_id'] ?? 0);
    
    if (!$aulaId) {
        echo json_encode(['success' => false, 'message' => 'ID da aula é obrigatório']);
        exit;
    }
    
    try {
        $materiais = $db->fetchAll(
            "SELECT * FROM materiais_complementares WHERE aula_id = ? AND ativo = TRUE ORDER BY ordem, titulo",
            [$aulaId]
        );
        
        echo json_encode([
            'success' => true,
            'materiais' => $materiais
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao buscar materiais: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar materiais. Tente novamente.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
