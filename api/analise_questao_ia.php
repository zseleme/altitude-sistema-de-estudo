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
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Validar CSRF token
CSRFHelper::validateRequest();

// Rate limiting para operações de IA
$rateLimiter = new RateLimiter();
$userId = getUserId();
$rateCheck = $rateLimiter->checkAIRateLimit($userId, 'ai_analysis');

if (!$rateCheck['allowed']) {
    RateLimiter::sendRateLimitResponse($rateCheck);
}

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $resposta_id = $data['resposta_id'] ?? 0;

    // Buscar detalhes da questão e resposta do usuário
    $query = "SELECT
              r.resposta_usuario,
              r.correta,
              q.enunciado,
              q.alternativa_a,
              q.alternativa_b,
              q.alternativa_c,
              q.alternativa_d,
              q.alternativa_e,
              q.resposta_correta,
              q.explicacao,
              q.nivel_dificuldade,
              s.disciplina,
              s.titulo as simulado_titulo
              FROM simulado_respostas r
              INNER JOIN simulado_questoes q ON r.questao_id = q.id
              INNER JOIN simulados s ON r.simulado_id = s.id
              WHERE r.id = :resposta_id AND r.usuario_id = :usuario_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':resposta_id', $resposta_id);
    $stmt->bindParam(':usuario_id', getUserId());
    $stmt->execute();

    $questao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$questao) {
        http_response_code(404);
        echo json_encode(['error' => 'Questão não encontrada']);
        exit;
    }

    // Se a resposta já foi correta, não precisa de análise
    if ($questao['correta']) {
        echo json_encode([
            'success' => true,
            'analise' => 'Parabéns! Você acertou esta questão.',
            'cached' => false
        ]);
        exit;
    }

    // Verificar se já existe análise
    $query = "SELECT analise_ia FROM simulado_respostas WHERE id = :id AND analise_ia IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $resposta_id);
    $stmt->execute();
    $analise_existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($analise_existente && !empty($analise_existente['analise_ia'])) {
        echo json_encode([
            'success' => true,
            'analise' => $analise_existente['analise_ia'],
            'cached' => true
        ]);
        exit;
    }

    // Montar alternativas
    $alternativas = [
        'A' => $questao['alternativa_a'],
        'B' => $questao['alternativa_b'],
        'C' => $questao['alternativa_c'],
        'D' => $questao['alternativa_d']
    ];
    if (!empty($questao['alternativa_e'])) {
        $alternativas['E'] = $questao['alternativa_e'];
    }

    $texto_alternativas = "";
    foreach ($alternativas as $letra => $texto) {
        $marcador = ($letra === $questao['resposta_usuario']) ? " ← SUA RESPOSTA" : "";
        if ($letra === $questao['resposta_correta']) {
            $marcador .= " ✓ CORRETA";
        }
        $texto_alternativas .= "\n{$letra}) {$texto}{$marcador}";
    }

    // Criar prompt para a IA (otimizado para ser mais conciso)
    $prompt = "Você é um professor experiente. Analise o erro do aluno de forma concisa.

QUESTÃO:
{$questao['enunciado']}

ALTERNATIVAS:{$texto_alternativas}

O aluno marcou {$questao['resposta_usuario']}, mas o correto é {$questao['resposta_correta']}.

Forneça uma análise BREVE e DIRETA em 3 partes:

📌 **Por que errou:**
[2-3 linhas explicando o erro]

💡 **Resposta correta:**
[2-3 linhas explicando a lógica]

🎯 **Dica para memorizar:**
[1-2 linhas com macete ou analogia]

Seja direto, claro e motivador. Máximo 200 palavras.";

    // Verificar se a IA está configurada
    if (!AIHelper::isConfigured()) {
        echo json_encode([
            'success' => true,
            'analise' => "⚙️ **Análise por IA não disponível**\n\nA análise automática por IA ainda não foi configurada pelo administrador do sistema.\n\nEnquanto isso, revise a explicação da questão e tente entender onde errou.",
            'not_configured' => true
        ]);
        exit;
    }

    // Chamar a IA
    try {
        $aiHelper = new AIHelper();
        $result = $aiHelper->analyzeQuestion($prompt);

        if (!isset($result['review'])) {
            throw new Exception('Erro ao gerar análise pela IA');
        }

        $analise = $result['review'];
    } catch (Exception $aiError) {
        // Se houver erro na IA, retornar mensagem amigável
        echo json_encode([
            'success' => true,
            'analise' => "⚠️ **Análise temporariamente indisponível**\n\nNão foi possível gerar a análise automática no momento.\n\n" . $aiError->getMessage() . "\n\nRevise a explicação da questão e tente entender onde errou.",
            'ai_error' => true
        ]);
        exit;
    }

    // Salvar análise no banco
    $query = "UPDATE simulado_respostas SET analise_ia = :analise WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':analise', $analise);
    $stmt->bindParam(':id', $resposta_id);
    $stmt->execute();

    // Registrar uso da API de IA para rate limiting
    $rateLimiter->recordRequest($userId, 'ai_analysis', 3600);
    $rateLimiter->recordRequest($userId, 'ai_analysis', 60);

    echo json_encode([
        'success' => true,
        'analise' => $analise,
        'cached' => false
    ]);

} catch(Exception $e) {
    error_log("Erro em analise_questao_ia.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar análise. Tente novamente.']);
}
