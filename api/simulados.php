<?php
session_start();
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();
$userId = getUserId(); // ID do usuário logado

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'listar':
            $query = "SELECT s.*,
                      COUNT(DISTINCT sq.id) as total_questoes,
                      (SELECT COUNT(*) FROM simulado_tentativas st
                       WHERE st.simulado_id = s.id AND st.usuario_id = :usuario_id AND st.finalizado = 1) as tentativas
                      FROM simulados s
                      LEFT JOIN simulado_questoes sq ON s.id = sq.simulado_id
                      WHERE s.ativo = 1
                      GROUP BY s.id
                      ORDER BY s.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'criar':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $query = "INSERT INTO simulados (titulo, descricao, disciplina, tempo_limite)
                      VALUES (:titulo, :descricao, :disciplina, :tempo_limite)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $data['titulo']);
            $stmt->bindParam(':descricao', $data['descricao']);
            $stmt->bindParam(':disciplina', $data['disciplina']);
            $stmt->bindParam(':tempo_limite', $data['tempo_limite']);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                throw new Exception('Erro ao criar simulado');
            }
            break;

        case 'detalhes':
            $id = $_GET['id'] ?? 0;

            $query = "SELECT * FROM simulados WHERE id = :id AND ativo = 1";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $simulado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$simulado) {
                http_response_code(404);
                echo json_encode(['error' => 'Simulado não encontrado']);
                exit;
            }

            // Buscar questões
            $query = "SELECT id, numero_questao, enunciado, alternativa_a, alternativa_b,
                      alternativa_c, alternativa_d, alternativa_e, nivel_dificuldade
                      FROM simulado_questoes
                      WHERE simulado_id = :id
                      ORDER BY numero_questao";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $simulado['questoes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($simulado);
            break;

        case 'iniciar':
            $simulado_id = $_POST['simulado_id'] ?? 0;

            // Verificar se já existe tentativa em andamento
            $query = "SELECT id FROM simulado_tentativas
                      WHERE usuario_id = :usuario_id AND simulado_id = :simulado_id
                      AND finalizado = 0
                      ORDER BY data_inicio DESC LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->bindParam(':simulado_id', $simulado_id);
            $stmt->execute();

            $tentativa = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tentativa) {
                echo json_encode(['success' => true, 'tentativa_id' => $tentativa['id'], 'continuacao' => true]);
            } else {
                // Criar nova tentativa
                $query = "INSERT INTO simulado_tentativas (usuario_id, simulado_id, questoes_totais)
                          SELECT :usuario_id, :simulado_id, COUNT(*)
                          FROM simulado_questoes WHERE simulado_id = :simulado_id";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':usuario_id', $userId);
                $stmt->bindParam(':simulado_id', $simulado_id);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'tentativa_id' => $db->lastInsertId(), 'continuacao' => false]);
                } else {
                    throw new Exception('Erro ao iniciar simulado');
                }
            }
            break;

        case 'responder':
            $data = json_decode(file_get_contents('php://input'), true);

            // Buscar resposta correta
            $query = "SELECT resposta_correta, explicacao FROM simulado_questoes WHERE id = :questao_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':questao_id', $data['questao_id']);
            $stmt->execute();

            $questao = $stmt->fetch(PDO::FETCH_ASSOC);
            $correta = ($data['resposta'] === $questao['resposta_correta']);

            // Verificar se já existe resposta
            $query = "SELECT id FROM simulado_respostas
                      WHERE usuario_id = :usuario_id AND simulado_id = :simulado_id AND questao_id = :questao_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->bindParam(':simulado_id', $data['simulado_id']);
            $stmt->bindParam(':questao_id', $data['questao_id']);
            $stmt->execute();
            $respostaExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($respostaExistente) {
                // Atualizar resposta existente
                $query = "UPDATE simulado_respostas
                          SET resposta_usuario = :resposta, correta = :correta, tempo_resposta = :tempo
                          WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':resposta', $data['resposta']);
                $stmt->bindParam(':correta', $correta, PDO::PARAM_INT);
                $stmt->bindParam(':tempo', $data['tempo_resposta']);
                $stmt->bindParam(':id', $respostaExistente['id']);
                $stmt->execute();
                $resposta_id = $respostaExistente['id'];
            } else {
                // Inserir nova resposta
                $query = "INSERT INTO simulado_respostas
                          (usuario_id, simulado_id, questao_id, resposta_usuario, correta, tempo_resposta)
                          VALUES (:usuario_id, :simulado_id, :questao_id, :resposta, :correta, :tempo)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':usuario_id', $userId);
                $stmt->bindParam(':simulado_id', $data['simulado_id']);
                $stmt->bindParam(':questao_id', $data['questao_id']);
                $stmt->bindParam(':resposta', $data['resposta']);
                $stmt->bindParam(':correta', $correta, PDO::PARAM_INT);
                $stmt->bindParam(':tempo', $data['tempo_resposta']);
                $stmt->execute();
                $resposta_id = $db->lastInsertId();
            }

            echo json_encode([
                'success' => true,
                'correta' => $correta,
                'resposta_correta' => $questao['resposta_correta'],
                'explicacao' => $questao['explicacao'],
                'resposta_id' => $resposta_id
            ]);
            break;

        case 'finalizar':
            $tentativa_id = $_POST['tentativa_id'] ?? 0;

            // Calcular nota
            $query = "SELECT
                      COUNT(*) as total,
                      SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) as corretas
                      FROM simulado_respostas r
                      INNER JOIN simulado_tentativas t ON r.simulado_id = t.simulado_id
                      WHERE t.id = :tentativa_id AND r.usuario_id = :usuario_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':tentativa_id', $tentativa_id);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $nota = ($resultado['total'] > 0) ? ($resultado['corretas'] / $resultado['total']) * 100 : 0;

            // Atualizar tentativa
            $query = "UPDATE simulado_tentativas
                      SET finalizado = 1, data_fim = CURRENT_TIMESTAMP, nota = :nota,
                          questoes_corretas = :corretas, questoes_totais = :total
                      WHERE id = :tentativa_id AND usuario_id = :usuario_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':nota', $nota);
            $stmt->bindParam(':corretas', $resultado['corretas']);
            $stmt->bindParam(':total', $resultado['total']);
            $stmt->bindParam(':tentativa_id', $tentativa_id);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'nota' => round($nota, 2),
                'corretas' => $resultado['corretas'],
                'total' => $resultado['total']
            ]);
            break;

        case 'resultado':
            $tentativa_id = $_GET['tentativa_id'] ?? 0;

            $query = "SELECT t.*, s.titulo, s.descricao, s.disciplina
                      FROM simulado_tentativas t
                      INNER JOIN simulados s ON t.simulado_id = s.id
                      WHERE t.id = :tentativa_id AND t.usuario_id = :usuario_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':tentativa_id', $tentativa_id);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->execute();

            $tentativa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tentativa) {
                http_response_code(404);
                echo json_encode(['error' => 'Tentativa não encontrada']);
                exit;
            }

            // Buscar respostas detalhadas
            $query = "SELECT r.*, q.enunciado, q.alternativa_a, q.alternativa_b,
                      q.alternativa_c, q.alternativa_d, q.alternativa_e,
                      q.resposta_correta, q.explicacao, q.numero_questao
                      FROM simulado_respostas r
                      INNER JOIN simulado_questoes q ON r.questao_id = q.id
                      WHERE r.usuario_id = :usuario_id AND r.simulado_id = :simulado_id
                      ORDER BY q.numero_questao";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->bindParam(':simulado_id', $tentativa['simulado_id']);
            $stmt->execute();

            $tentativa['respostas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($tentativa);
            break;

        case 'editar':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            $query = "UPDATE simulados
                      SET titulo = :titulo,
                          descricao = :descricao,
                          disciplina = :disciplina,
                          tempo_limite = :tempo_limite
                      WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $data['titulo']);
            $stmt->bindParam(':descricao', $data['descricao']);
            $stmt->bindParam(':disciplina', $data['disciplina']);
            $stmt->bindParam(':tempo_limite', $data['tempo_limite']);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao editar simulado');
            }
            break;

        case 'excluir':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit;
            }

            $id = $_POST['id'] ?? $_GET['id'] ?? 0;

            // Soft delete - marcar como inativo
            $query = "UPDATE simulados SET ativo = 0 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao excluir simulado');
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
