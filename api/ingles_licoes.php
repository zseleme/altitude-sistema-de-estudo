<?php
/**
 * API para Lições de Inglês Geradas por IA
 * Endpoints: gerar, listar, detalhes, iniciar, responder, finalizar, resultado, excluir
 */

require_once '../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'gerar':
            // Gerar nova lição com IA
            $tema = $_POST['tema'] ?? '';
            $nivel = $_POST['nivel'] ?? 'intermediario';

            if (empty($tema)) {
                echo json_encode(['success' => false, 'message' => 'Tema é obrigatório']);
                exit;
            }

            if (!in_array($nivel, ['basico', 'intermediario', 'avancado'])) {
                $nivel = 'intermediario';
            }

            // Verificar se IA está configurada
            require_once '../includes/ai_helper.php';
            if (!AIHelper::isConfigured()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'IA não configurada. Configure em Administração > Configurações de IA.'
                ]);
                exit;
            }

            // Gerar lição com IA
            $ai = new AIHelper();
            $result = $ai->generateEnglishLesson($tema, $nivel);

            // Extrair JSON da resposta
            $jsonContent = $result['review'] ?? '';

            // Remover markdown code blocks se existir
            $jsonContent = preg_replace('/^```json\s*/', '', $jsonContent);
            $jsonContent = preg_replace('/\s*```$/', '', $jsonContent);
            $jsonContent = trim($jsonContent);

            $licaoData = json_decode($jsonContent, true);

            if (!$licaoData || !isset($licaoData['titulo']) || !isset($licaoData['questoes'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao processar resposta da IA. JSON inválido.',
                    'debug' => $jsonContent
                ]);
                exit;
            }

            // Validar estrutura
            if (count($licaoData['questoes']) !== 9) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Lição deve conter exatamente 9 questões'
                ]);
                exit;
            }

            // Salvar lição no banco
            $stmt = $db->execute("
                INSERT INTO ingles_licoes (
                    usuario_id, tema, titulo, descricao, nivel, conteudo_apoio, numero_questoes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [
                $userId,
                $tema,
                $licaoData['titulo'],
                $licaoData['descricao'] ?? '',
                $nivel,
                $licaoData['conteudo_apoio'] ?? '',
                9
            ]);

            $licaoId = $db->lastInsertId();

            // Salvar questões
            foreach ($licaoData['questoes'] as $questao) {
                $respostasAceitas = isset($questao['respostas_aceitas'])
                    ? json_encode($questao['respostas_aceitas'])
                    : null;

                $criterios = isset($questao['criterios_avaliacao'])
                    ? json_encode($questao['criterios_avaliacao'])
                    : null;

                $db->execute("
                    INSERT INTO ingles_licao_questoes (
                        licao_id, numero_questao, tipo_questao, enunciado, contexto,
                        alternativa_a, alternativa_b, alternativa_c, alternativa_d,
                        resposta_correta_multipla, texto_com_lacuna, resposta_correta_lacuna,
                        respostas_aceitas, prompt_escrita, criterios_avaliacao, explicacao, dicas
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $licaoId,
                    $questao['numero_questao'],
                    $questao['tipo_questao'],
                    $questao['enunciado'] ?? '',
                    $questao['contexto'] ?? null,
                    $questao['alternativa_a'] ?? null,
                    $questao['alternativa_b'] ?? null,
                    $questao['alternativa_c'] ?? null,
                    $questao['alternativa_d'] ?? null,
                    $questao['resposta_correta_multipla'] ?? null,
                    $questao['texto_com_lacuna'] ?? null,
                    $questao['resposta_correta_lacuna'] ?? null,
                    $respostasAceitas,
                    $questao['prompt_escrita'] ?? null,
                    $criterios,
                    $questao['explicacao'] ?? null,
                    $questao['dicas'] ?? null
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Lição gerada com sucesso!',
                'lesson_id' => $licaoId,
                'tokens_used' => $result['tokens_used'] ?? 0
            ]);
            break;

        case 'listar':
            // Listar lições do usuário
            $licoes = $db->fetchAll("
                SELECT
                    l.*,
                    COUNT(DISTINCT t.id) as tentativas_count,
                    MAX(t.nota) as melhor_nota
                FROM ingles_licoes l
                LEFT JOIN ingles_licao_tentativas t ON l.id = t.licao_id AND t.usuario_id = l.usuario_id
                WHERE l.usuario_id = ? AND l.ativo = 1
                GROUP BY l.id
                ORDER BY l.created_at DESC
            ", [$userId]);

            echo json_encode([
                'success' => true,
                'licoes' => $licoes
            ]);
            break;

        case 'detalhes':
            // Obter detalhes da lição com questões
            $id = $_GET['id'] ?? 0;

            $licao = $db->fetchOne("
                SELECT * FROM ingles_licoes
                WHERE id = ? AND usuario_id = ? AND ativo = 1
            ", [$id, $userId]);

            if (!$licao) {
                echo json_encode(['success' => false, 'message' => 'Lição não encontrada']);
                exit;
            }

            $questoes = $db->fetchAll("
                SELECT
                    id, numero_questao, tipo_questao, enunciado, contexto,
                    alternativa_a, alternativa_b, alternativa_c, alternativa_d,
                    texto_com_lacuna, prompt_escrita, criterios_avaliacao, dicas
                FROM ingles_licao_questoes
                WHERE licao_id = ?
                ORDER BY numero_questao
            ", [$id]);

            // Decodificar JSON fields
            foreach ($questoes as &$questao) {
                if ($questao['criterios_avaliacao']) {
                    $questao['criterios_avaliacao'] = json_decode($questao['criterios_avaliacao'], true);
                }
            }

            echo json_encode([
                'success' => true,
                'licao' => $licao,
                'questoes' => $questoes
            ]);
            break;

        case 'iniciar':
            // Iniciar nova tentativa
            $licaoId = $_POST['licao_id'] ?? 0;

            // Verificar se lição existe e pertence ao usuário
            $licao = $db->fetchOne("
                SELECT * FROM ingles_licoes
                WHERE id = ? AND usuario_id = ? AND ativo = 1
            ", [$licaoId, $userId]);

            if (!$licao) {
                echo json_encode(['success' => false, 'message' => 'Lição não encontrada']);
                exit;
            }

            // Verificar se já existe tentativa não finalizada
            $tentativaExistente = $db->fetchOne("
                SELECT id FROM ingles_licao_tentativas
                WHERE licao_id = ? AND usuario_id = ? AND finalizado = 0
                ORDER BY data_inicio DESC
                LIMIT 1
            ", [$licaoId, $userId]);

            if ($tentativaExistente) {
                echo json_encode([
                    'success' => true,
                    'tentativa_id' => $tentativaExistente['id'],
                    'continuacao' => true
                ]);
                exit;
            }

            // Criar nova tentativa
            $db->execute("
                INSERT INTO ingles_licao_tentativas (
                    usuario_id, licao_id, questoes_totais
                ) VALUES (?, ?, ?)
            ", [$userId, $licaoId, $licao['numero_questoes']]);

            $tentativaId = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'tentativa_id' => $tentativaId,
                'continuacao' => false
            ]);
            break;

        case 'responder':
            // Responder questão
            $tentativaId = $_POST['tentativa_id'] ?? 0;
            $questaoId = $_POST['questao_id'] ?? 0;
            $tipoQuestao = $_POST['tipo_questao'] ?? '';
            $tempoResposta = $_POST['tempo_resposta'] ?? 0;

            // Verificar se tentativa pertence ao usuário
            $tentativa = $db->fetchOne("
                SELECT * FROM ingles_licao_tentativas
                WHERE id = ? AND usuario_id = ?
            ", [$tentativaId, $userId]);

            if (!$tentativa) {
                echo json_encode(['success' => false, 'message' => 'Tentativa não encontrada']);
                exit;
            }

            // Obter detalhes da questão
            $questao = $db->fetchOne("
                SELECT * FROM ingles_licao_questoes
                WHERE id = ? AND licao_id = ?
            ", [$questaoId, $tentativa['licao_id']]);

            if (!$questao) {
                echo json_encode(['success' => false, 'message' => 'Questão não encontrada']);
                exit;
            }

            $correta = 0;
            $pontuacao = null;
            $analiseIA = null;
            $respostaMultipla = null;
            $respostaLacuna = null;
            $respostaEscrita = null;

            // Processar resposta baseado no tipo
            switch ($tipoQuestao) {
                case 'multipla_escolha':
                    $respostaMultipla = $_POST['resposta_multipla'] ?? '';
                    $correta = (strtoupper($respostaMultipla) === strtoupper($questao['resposta_correta_multipla'])) ? 1 : 0;
                    break;

                case 'preencher_lacuna':
                    $respostaLacuna = $_POST['resposta_lacuna'] ?? '';
                    $respostaUsuario = strtolower(trim($respostaLacuna));

                    // Verificar contra respostas aceitas
                    $aceitasArray = json_decode($questao['respostas_aceitas'], true) ?: [];
                    $aceitasArray[] = $questao['resposta_correta_lacuna'];
                    $aceitasArray = array_map('strtolower', $aceitasArray);

                    $correta = in_array($respostaUsuario, $aceitasArray) ? 1 : 0;
                    break;

                case 'escrita':
                    $respostaEscrita = $_POST['resposta_escrita'] ?? '';

                    // Validar mínimo de palavras (aproximado)
                    $palavras = str_word_count($respostaEscrita);
                    if ($palavras < 30) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Resposta muito curta. Escreva pelo menos 30 palavras.'
                        ]);
                        exit;
                    }

                    // Avaliar com IA
                    require_once '../includes/ai_helper.php';
                    $ai = new AIHelper();
                    $criterios = json_decode($questao['criterios_avaliacao'], true) ?: ['Grammar', 'Vocabulary', 'Coherence'];

                    $avaliacaoResult = $ai->evaluateWriting(
                        $questao['prompt_escrita'],
                        $criterios,
                        $respostaEscrita
                    );

                    // Extrair JSON da resposta
                    $jsonContent = $avaliacaoResult['review'] ?? '';
                    $jsonContent = preg_replace('/^```json\s*/', '', $jsonContent);
                    $jsonContent = preg_replace('/\s*```$/', '', $jsonContent);
                    $jsonContent = trim($jsonContent);

                    $avaliacao = json_decode($jsonContent, true);

                    if ($avaliacao && isset($avaliacao['pontuacao'])) {
                        $pontuacao = $avaliacao['pontuacao'];
                        $correta = $pontuacao >= 70 ? 1 : 0;
                        $analiseIA = json_encode($avaliacao, JSON_UNESCAPED_UNICODE);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Erro ao processar avaliação da IA'
                        ]);
                        exit;
                    }
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Tipo de questão inválido']);
                    exit;
            }

            // Verificar se já existe resposta para esta questão
            $respostaExistente = $db->fetchOne("
                SELECT id FROM ingles_licao_respostas
                WHERE tentativa_id = ? AND questao_id = ?
            ", [$tentativaId, $questaoId]);

            if ($respostaExistente) {
                // Atualizar resposta existente
                $db->execute("
                    UPDATE ingles_licao_respostas
                    SET resposta_multipla = ?, resposta_lacuna = ?, resposta_escrita = ?,
                        correta = ?, pontuacao = ?, analise_ia = ?, tempo_resposta = ?
                    WHERE id = ?
                ", [
                    $respostaMultipla, $respostaLacuna, $respostaEscrita,
                    $correta, $pontuacao, $analiseIA, $tempoResposta,
                    $respostaExistente['id']
                ]);
            } else {
                // Inserir nova resposta
                $db->execute("
                    INSERT INTO ingles_licao_respostas (
                        usuario_id, licao_id, questao_id, tentativa_id, tipo_questao,
                        resposta_multipla, resposta_lacuna, resposta_escrita,
                        correta, pontuacao, analise_ia, tempo_resposta
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $userId, $tentativa['licao_id'], $questaoId, $tentativaId, $tipoQuestao,
                    $respostaMultipla, $respostaLacuna, $respostaEscrita,
                    $correta, $pontuacao, $analiseIA, $tempoResposta
                ]);
            }

            $response = [
                'success' => true,
                'correta' => $correta,
                'resposta_correta' => $questao['resposta_correta_multipla'] ?? $questao['resposta_correta_lacuna'] ?? null,
                'explicacao' => $questao['explicacao']
            ];

            if ($tipoQuestao === 'escrita' && $avaliacao) {
                $response['avaliacao'] = $avaliacao;
            }

            if ($tipoQuestao === 'preencher_lacuna' && !$correta) {
                $response['respostas_aceitas'] = json_decode($questao['respostas_aceitas'], true);
            }

            echo json_encode($response);
            break;

        case 'finalizar':
            // Finalizar tentativa
            $tentativaId = $_POST['tentativa_id'] ?? 0;

            $tentativa = $db->fetchOne("
                SELECT * FROM ingles_licao_tentativas
                WHERE id = ? AND usuario_id = ?
            ", [$tentativaId, $userId]);

            if (!$tentativa) {
                echo json_encode(['success' => false, 'message' => 'Tentativa não encontrada']);
                exit;
            }

            // Calcular nota
            $respostas = $db->fetchAll("
                SELECT correta, pontuacao FROM ingles_licao_respostas
                WHERE tentativa_id = ?
            ", [$tentativaId]);

            $totalQuestoes = count($respostas);
            $questoesCorretas = 0;
            $somaNotas = 0;

            foreach ($respostas as $r) {
                if ($r['pontuacao'] !== null) {
                    // Questão de escrita
                    $somaNotas += $r['pontuacao'];
                } else {
                    // Questão objetiva
                    if ($r['correta']) {
                        $questoesCorretas++;
                        $somaNotas += 100;
                    }
                }
            }

            $notaFinal = $totalQuestoes > 0 ? ($somaNotas / $totalQuestoes) : 0;

            // Atualizar tentativa
            $db->execute("
                UPDATE ingles_licao_tentativas
                SET finalizado = 1, nota = ?, questoes_corretas = ?,
                    data_fim = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$notaFinal, $questoesCorretas, $tentativaId]);

            echo json_encode([
                'success' => true,
                'nota' => round($notaFinal, 2),
                'questoes_corretas' => $questoesCorretas,
                'questoes_totais' => $totalQuestoes
            ]);
            break;

        case 'resultado':
            // Obter resultado completo
            $tentativaId = $_GET['tentativa_id'] ?? 0;

            $tentativa = $db->fetchOne("
                SELECT t.*, l.titulo, l.tema, l.nivel
                FROM ingles_licao_tentativas t
                JOIN ingles_licoes l ON t.licao_id = l.id
                WHERE t.id = ? AND t.usuario_id = ?
            ", [$tentativaId, $userId]);

            if (!$tentativa) {
                echo json_encode(['success' => false, 'message' => 'Tentativa não encontrada']);
                exit;
            }

            $respostas = $db->fetchAll("
                SELECT r.*, q.*
                FROM ingles_licao_respostas r
                JOIN ingles_licao_questoes q ON r.questao_id = q.id
                WHERE r.tentativa_id = ?
                ORDER BY q.numero_questao
            ", [$tentativaId]);

            // Decodificar JSON fields
            foreach ($respostas as &$r) {
                if ($r['analise_ia']) {
                    $r['analise_ia'] = json_decode($r['analise_ia'], true);
                }
                if ($r['criterios_avaliacao']) {
                    $r['criterios_avaliacao'] = json_decode($r['criterios_avaliacao'], true);
                }
                if ($r['respostas_aceitas']) {
                    $r['respostas_aceitas'] = json_decode($r['respostas_aceitas'], true);
                }
            }

            echo json_encode([
                'success' => true,
                'tentativa' => $tentativa,
                'respostas' => $respostas
            ]);
            break;

        case 'excluir':
            // Excluir lição (soft delete)
            $id = $_POST['id'] ?? 0;

            $licao = $db->fetchOne("
                SELECT * FROM ingles_licoes
                WHERE id = ? AND usuario_id = ?
            ", [$id, $userId]);

            if (!$licao) {
                echo json_encode(['success' => false, 'message' => 'Lição não encontrada']);
                exit;
            }

            $db->execute("
                UPDATE ingles_licoes
                SET ativo = 0
                WHERE id = ?
            ", [$id]);

            echo json_encode([
                'success' => true,
                'message' => 'Lição excluída com sucesso'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
