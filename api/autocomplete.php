<?php
/**
 * API de Autocomplete para busca
 * Retorna sugestões de cursos e aulas conforme o usuário digita
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security_headers.php';
requireLogin();

// Apply minimal security headers for API
SecurityHeaders::applyMinimal();

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

try {
    $db = Database::getInstance();
    $searchTerm = '%' . $query . '%';

    // Determinar operador LIKE baseado no tipo de banco
    $likeOp = $db->isPostgreSQL() ? 'ILIKE' : 'LIKE';
    $true = $db->getBoolTrue();

    // Buscar cursos
    $cursos = $db->fetchAll("
        SELECT
            c.id,
            c.titulo,
            cat.nome as categoria_nome,
            'curso' as tipo
        FROM cursos c
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        WHERE c.ativo = ?
          AND c.titulo $likeOp ?
        ORDER BY c.titulo
        LIMIT 5
    ", [$true, $searchTerm]);

    // Buscar aulas
    $aulas = $db->fetchAll("
        SELECT
            a.id,
            a.titulo,
            c.titulo as curso_titulo,
            'aula' as tipo
        FROM aulas a
        JOIN cursos c ON a.curso_id = c.id
        WHERE a.ativo = ?
          AND c.ativo = ?
          AND a.titulo $likeOp ?
        ORDER BY a.titulo
        LIMIT 5
    ", [$true, $true, $searchTerm]);
    
    // Combinar resultados
    $results = [];
    
    foreach ($cursos as $curso) {
        $results[] = [
            'id' => $curso['id'],
            'titulo' => $curso['titulo'],
            'subtitulo' => $curso['categoria_nome'] ?? 'Curso',
            'tipo' => 'curso',
            'url' => '/curso.php?id=' . $curso['id'],
            'icon' => 'fa-book'
        ];
    }
    
    foreach ($aulas as $aula) {
        $results[] = [
            'id' => $aula['id'],
            'titulo' => $aula['titulo'],
            'subtitulo' => $aula['curso_titulo'],
            'tipo' => 'aula',
            'url' => '/aula.php?id=' . $aula['id'],
            'icon' => 'fa-play-circle'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log("Erro em autocomplete.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar resultados. Tente novamente.'
    ]);
}

