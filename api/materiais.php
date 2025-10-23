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
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar material: ' . $e->getMessage()]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Atualizar material
    parse_str(file_get_contents('php://input'), $_PUT);
    
    $materialId = (int)($_PUT['id'] ?? 0);
    $titulo = trim($_PUT['titulo'] ?? '');
    $descricao = trim($_PUT['descricao'] ?? '');
    $tipo = $_PUT['tipo'] ?? 'outro';
    $urlArquivo = trim($_PUT['url_arquivo'] ?? '');
    $nomeArquivo = trim($_PUT['nome_arquivo'] ?? '');
    $tamanhoArquivo = (int)($_PUT['tamanho_arquivo'] ?? 0);
    $ordem = (int)($_PUT['ordem'] ?? 1);
    
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
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar material: ' . $e->getMessage()]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
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
        echo json_encode(['success' => false, 'message' => 'Erro ao remover material: ' . $e->getMessage()]);
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
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar materiais: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
