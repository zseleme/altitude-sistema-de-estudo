<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Buscar informações do usuário
$usuario = $db->fetchOne("SELECT nome, email FROM usuarios WHERE id = ?", [$userId]);

// Buscar todas as entradas do diário (sem limite de data para exportação completa)
$entradas = $db->fetchAll("
    SELECT data_entrada, conteudo, humor, tags, data_atualizacao
    FROM ingles_diario
    WHERE usuario_id = ?
    ORDER BY data_entrada DESC
", [$userId]);

if (empty($entradas)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Você ainda não possui entradas no diário de inglês.";
    exit;
}

// Preparar conteúdo do arquivo
$content = "========================================\n";
$content .= "  DIÁRIO DE INGLÊS - ALTITUDE PLATFORM\n";
$content .= "========================================\n\n";
$content .= "Aluno: " . $usuario['nome'] . "\n";
$content .= "E-mail: " . $usuario['email'] . "\n";
$content .= "Data da exportação: " . date('d/m/Y H:i:s') . "\n";
$content .= "Total de entradas: " . count($entradas) . "\n\n";
$content .= "========================================\n\n";

// Labels de humor
$humorLabels = [
    'otimo' => 'Ótimo 😄',
    'bom' => 'Bom 🙂',
    'neutro' => 'Neutro 😐',
    'ruim' => 'Ruim 😞',
    'pessimo' => 'Péssimo 😢'
];

// Adicionar cada entrada
foreach ($entradas as $index => $entrada) {
    $numero = $index + 1;
    $dataFormatada = date('d/m/Y (l)', strtotime($entrada['data_entrada']));

    $content .= "----------------------------------------\n";
    $content .= "ENTRADA #{$numero} - {$dataFormatada}\n";
    $content .= "----------------------------------------\n\n";

    // Humor
    if (!empty($entrada['humor']) && isset($humorLabels[$entrada['humor']])) {
        $content .= "Humor: " . $humorLabels[$entrada['humor']] . "\n\n";
    }

    // Conteúdo
    $content .= wordwrap($entrada['conteudo'], 70, "\n") . "\n\n";

    // Tags
    if (!empty($entrada['tags'])) {
        $content .= "Tags: " . $entrada['tags'] . "\n\n";
    }

    // Data de atualização
    $content .= "Última atualização: " . date('d/m/Y H:i', strtotime($entrada['data_atualizacao'])) . "\n\n";
}

$content .= "========================================\n";
$content .= "Fim do diário\n";
$content .= "========================================\n";

// Definir nome do arquivo
$filename = 'diario-ingles-' . date('Y-m-d') . '.txt';

// Headers para download
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Enviar conteúdo
echo $content;
exit;
