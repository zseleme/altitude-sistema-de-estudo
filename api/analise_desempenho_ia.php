<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/ai_helper.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/rate_limiter.php';
require_once '../includes/security_headers.php';

// Apply minimal security headers for API
SecurityHeaders::applyMinimal();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Validar CSRF token
CSRFHelper::validateRequest();

// Rate limiting para operações de IA
$rateLimiter = new RateLimiter();
$userId = getUserId();
$rateCheck = $rateLimiter->checkAIRateLimit($userId, 'ai_performance');

if (!$rateCheck['allowed']) {
    RateLimiter::sendRateLimitResponse($rateCheck);
}

$database = Database::getInstance();
$db = $database->getConnection();
$userId = getUserId();

$tentativa_id = $_POST['tentativa_id'] ?? 0;

try {
    // Buscar informações da tentativa
    $query = "SELECT t.*, s.titulo, s.disciplina
              FROM simulado_tentativas t
              INNER JOIN simulados s ON t.simulado_id = s.id
              WHERE t.id = :tentativa_id AND t.usuario_id = :usuario_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':tentativa_id', $tentativa_id);
    $stmt->bindParam(':usuario_id', $userId);
    $stmt->execute();

    $tentativa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tentativa) {
        throw new Exception('Tentativa não encontrada');
    }

    // Buscar respostas incorretas com detalhes
    $query = "SELECT r.*, q.enunciado, q.tags, q.nivel_dificuldade
              FROM simulado_respostas r
              INNER JOIN simulado_questoes q ON r.questao_id = q.id
              WHERE r.usuario_id = :usuario_id
              AND r.simulado_id = :simulado_id
              AND r.correta = 0
              ORDER BY q.numero_questao";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario_id', $userId);
    $stmt->bindParam(':simulado_id', $tentativa['simulado_id']);
    $stmt->execute();

    $questoesErradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar se a IA está configurada
    if (!AIHelper::isConfigured()) {
        throw new Exception('IA não configurada. Configure nas opções de administração.');
    }

    $ai = new AIHelper();

    // Preparar resumo das questões erradas
    $resumoErros = [];
    $tagsErradas = [];
    $nivelDificuldade = ['facil' => 0, 'medio' => 0, 'dificil' => 0];

    foreach ($questoesErradas as $questao) {
        // Extrair tema principal do enunciado (primeiras 100 caracteres)
        $tema = substr($questao['enunciado'], 0, 100) . '...';
        $resumoErros[] = $tema;

        // Coletar tags
        if ($questao['tags']) {
            $tags = explode(',', $questao['tags']);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $tagsErradas[] = $tag;
                }
            }
        }

        // Contar por nível de dificuldade
        if (isset($questao['nivel_dificuldade']) && isset($nivelDificuldade[$questao['nivel_dificuldade']])) {
            $nivelDificuldade[$questao['nivel_dificuldade']]++;
        }
    }

    // Identificar tags mais frequentes
    $tagsFrequentes = array_count_values($tagsErradas);
    arsort($tagsFrequentes);
    $topTags = array_slice(array_keys($tagsFrequentes), 0, 5);

    // Montar prompt para a IA
    $systemPrompt = "Você é um professor experiente que analisa o desempenho de estudantes em simulados e oferece orientações de estudo personalizadas.";

    $nota = $tentativa['nota'];
    $corretas = $tentativa['questoes_corretas'];
    $total = $tentativa['questoes_totais'];
    $erradas = $total - $corretas;
    $disciplina = $tentativa['disciplina'] ?: 'geral';

    $userPrompt = "Analise este desempenho de um estudante:\n\n";
    $userPrompt .= "**Simulado:** {$tentativa['titulo']}\n";
    $userPrompt .= "**Disciplina:** {$disciplina}\n";
    $userPrompt .= "**Nota Final:** {$nota}%\n";
    $userPrompt .= "**Acertos:** {$corretas} de {$total} questões\n";
    $userPrompt .= "**Erros:** {$erradas} questões\n\n";

    if (!empty($topTags)) {
        $userPrompt .= "**Temas com mais erros:** " . implode(', ', $topTags) . "\n\n";
    }

    if ($nivelDificuldade['facil'] > 0) {
        $userPrompt .= "**Erros em questões fáceis:** {$nivelDificuldade['facil']}\n";
    }
    if ($nivelDificuldade['medio'] > 0) {
        $userPrompt .= "**Erros em questões médias:** {$nivelDificuldade['medio']}\n";
    }
    if ($nivelDificuldade['dificil'] > 0) {
        $userPrompt .= "**Erros em questões difíceis:** {$nivelDificuldade['dificil']}\n";
    }

    $userPrompt .= "\nForneça uma análise detalhada e personalizada em formato Markdown com as seguintes seções:\n\n";
    $userPrompt .= "## 📊 Avaliação do Desempenho\n";
    $userPrompt .= "Faça uma análise honesta e encorajadora do desempenho (2-3 linhas).\n\n";
    $userPrompt .= "## 🎯 Pontos Fortes\n";
    $userPrompt .= "Liste 2-3 aspectos positivos observados.\n\n";
    $userPrompt .= "## 📚 Temas Prioritários para Estudo\n";
    $userPrompt .= "Liste 3-5 tópicos específicos que o estudante deve focar baseado nos erros.\n\n";
    $userPrompt .= "## 💡 Recomendações de Estudo\n";
    $userPrompt .= "Dê 3-4 dicas práticas e objetivas de como estudar melhor esses temas.\n\n";
    $userPrompt .= "Mantenha o tom motivador e construtivo. Use linguagem clara e direta.";

    // Chamar IA
    $analise = $ai->generateContent($systemPrompt, $userPrompt);

    if (!$analise) {
        throw new Exception('Erro ao gerar análise pela IA');
    }

    // Salvar análise no banco (opcional, para histórico)
    $query = "UPDATE simulado_tentativas
              SET analise_ia_desempenho = :analise
              WHERE id = :tentativa_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':analise', $analise);
    $stmt->bindParam(':tentativa_id', $tentativa_id);
    $stmt->execute();

    // Registrar uso da API de IA para rate limiting
    $rateLimiter->recordRequest($userId, 'ai_performance', 3600);
    $rateLimiter->recordRequest($userId, 'ai_performance', 60);

    echo json_encode([
        'success' => true,
        'analise' => $analise
    ]);

} catch (Exception $e) {
    error_log("Erro em analise_desempenho_ia.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar análise de desempenho. Tente novamente.'
    ]);
}
