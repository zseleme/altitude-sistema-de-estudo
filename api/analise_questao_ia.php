<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/gemini.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
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

    // Criar prompt para a IA
    $prompt = "Você é um professor de cursinho extremamente experiente e didático, especializado em ajudar alunos a compreenderem seus erros e memorizarem a lógica das questões através de técnicas mnemônicas e analogias criativas.

CONTEXTO:
- Disciplina: {$questao['disciplina']}
- Simulado: {$questao['simulado_titulo']}
- Nível: {$questao['nivel_dificuldade']}

QUESTÃO:
{$questao['enunciado']}

ALTERNATIVAS:{$texto_alternativas}

O aluno marcou a alternativa {$questao['resposta_usuario']}, mas a resposta correta é {$questao['resposta_correta']}.

SUA MISSÃO:
Forneça uma análise pedagógica que ajude o aluno a:
1. Entender POR QUE errou (qual foi o raciocínio equivocado)
2. Compreender a LÓGICA da resposta correta
3. MEMORIZAR o conceito através de:
   - Uma técnica mnemônica criativa
   - Uma analogia do dia a dia
   - Um macete ou regra prática

ESTRUTURA DA RESPOSTA:
📌 **Por que você errou:**
[Explique gentilmente o erro de raciocínio]

💡 **A lógica correta:**
[Explique o conceito de forma clara e objetiva]

🎯 **Para nunca mais esquecer:**
[Técnica mnemônica, analogia ou macete memorável]

⚡ **Dica rápida:**
[Uma frase curta que resume tudo]

Seja empático, motivador e use linguagem simples. Foque em fazer o aluno ENTENDER e MEMORIZAR, não apenas decorar.";

    // Chamar a API do Gemini
    $gemini = new GeminiAPI();
    $analise = $gemini->generateText($prompt);

    if (!$analise) {
        throw new Exception('Erro ao gerar análise pela IA');
    }

    // Salvar análise no banco
    $query = "UPDATE simulado_respostas SET analise_ia = :analise WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':analise', $analise);
    $stmt->bindParam(':id', $resposta_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'analise' => $analise,
        'cached' => false
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
