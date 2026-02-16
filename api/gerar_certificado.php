<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../lib/CertificateGenerator.php';

$db = Database::getInstance();
$usuarioId = $_SESSION['user_id'];

// Obter ID do curso via GET
$cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

if (!$cursoId) {
    http_response_code(400);
    die('Curso não especificado');
}

// Buscar informações do curso
$curso = $db->fetchOne("
    SELECT c.*, cat.nome as categoria_nome
    FROM cursos c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    WHERE c.id = ? AND c.ativo = TRUE
", [$cursoId]);

if (!$curso) {
    http_response_code(404);
    die('Curso não encontrado');
}

// Buscar informações do usuário
$usuario = $db->fetchOne("SELECT nome, email FROM usuarios WHERE id = ?", [$usuarioId]);

if (!$usuario) {
    http_response_code(404);
    die('Usuário não encontrado');
}

// Verificar se o usuário concluiu o curso
$aulas = $db->fetchAll("SELECT id FROM aulas WHERE curso_id = ? AND ativo = TRUE", [$cursoId]);
$totalAulas = count($aulas);

if ($totalAulas === 0) {
    http_response_code(400);
    die('Este curso não possui aulas');
}

$aulasConcluidas = $db->fetchAll("
    SELECT pa.aula_id, pa.data_conclusao
    FROM progresso_aulas pa
    WHERE pa.usuario_id = ?
      AND pa.aula_id IN (SELECT id FROM aulas WHERE curso_id = ? AND ativo = TRUE)
      AND pa.concluida = TRUE
", [$usuarioId, $cursoId]);

$totalConcluidas = count($aulasConcluidas);

// Verificar se concluiu 100% do curso
if ($totalConcluidas < $totalAulas) {
    http_response_code(403);
    die('Você ainda não concluiu todas as aulas deste curso. Complete ' . ($totalAulas - $totalConcluidas) . ' aula(s) restante(s) para obter o certificado.');
}

// Buscar data de conclusão (última aula concluída)
$datasConclusao = array_column($aulasConcluidas, 'data_conclusao');
sort($datasConclusao);
$dataConclusao = end($datasConclusao);

// Calcular carga horária total (soma da duração de todas as aulas)
$cargaHoraria = $db->fetchOne("
    SELECT COALESCE(SUM(duracao_minutos), 0) as total
    FROM aulas
    WHERE curso_id = ? AND ativo = TRUE
", [$cursoId]);

$cargaHorasTotal = ceil(($cargaHoraria['total'] ?? 0) / 60);

// Gerar certificado
$certificate = new CertificateGenerator(
    $usuario['nome'],
    $curso['titulo'],
    $dataConclusao,
    $cargaHorasTotal,
    $totalAulas
);

// Exibir certificado (usuário pode salvar como PDF usando Ctrl+P)
$certificate->output();
