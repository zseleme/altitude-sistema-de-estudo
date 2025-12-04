<?php
session_start();
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'cadastrar_massivo':
            $data = json_decode(file_get_contents('php://input'), true);
            $simulado_id = $data['simulado_id'];
            $questoes = $data['questoes'];

            $db->beginTransaction();

            try {
                $query = "INSERT INTO simulado_questoes
                          (simulado_id, numero_questao, enunciado, alternativa_a, alternativa_b,
                           alternativa_c, alternativa_d, alternativa_e, resposta_correta,
                           explicacao, nivel_dificuldade, tags)
                          VALUES
                          (:simulado_id, :numero, :enunciado, :alt_a, :alt_b, :alt_c, :alt_d,
                           :alt_e, :resposta, :explicacao, :nivel, :tags)";

                $stmt = $db->prepare($query);

                foreach ($questoes as $index => $questao) {
                    $numero = $index + 1;
                    $stmt->bindParam(':simulado_id', $simulado_id);
                    $stmt->bindParam(':numero', $numero);
                    $stmt->bindParam(':enunciado', $questao['enunciado']);
                    $stmt->bindParam(':alt_a', $questao['alternativa_a']);
                    $stmt->bindParam(':alt_b', $questao['alternativa_b']);
                    $stmt->bindParam(':alt_c', $questao['alternativa_c']);
                    $stmt->bindParam(':alt_d', $questao['alternativa_d']);
                    $stmt->bindParam(':alt_e', $questao['alternativa_e']);
                    $stmt->bindParam(':resposta', $questao['resposta_correta']);
                    $stmt->bindParam(':explicacao', $questao['explicacao']);
                    $stmt->bindParam(':nivel', $questao['nivel_dificuldade']);
                    $stmt->bindParam(':tags', $questao['tags']);
                    $stmt->execute();
                }

                // Atualizar contador de questões no simulado
                $query = "UPDATE simulados SET numero_questoes = :total WHERE id = :id";
                $stmt = $db->prepare($query);
                $total = count($questoes);
                $stmt->bindParam(':total', $total);
                $stmt->bindParam(':id', $simulado_id);
                $stmt->execute();

                $db->commit();
                echo json_encode(['success' => true, 'total' => $total]);

            } catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'listar':
            $simulado_id = $_GET['simulado_id'] ?? 0;

            $query = "SELECT * FROM simulado_questoes
                      WHERE simulado_id = :simulado_id
                      ORDER BY numero_questao";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':simulado_id', $simulado_id);
            $stmt->execute();

            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'editar':
            $data = json_decode(file_get_contents('php://input'), true);

            $query = "UPDATE simulado_questoes SET
                      enunciado = :enunciado,
                      alternativa_a = :alt_a,
                      alternativa_b = :alt_b,
                      alternativa_c = :alt_c,
                      alternativa_d = :alt_d,
                      alternativa_e = :alt_e,
                      resposta_correta = :resposta,
                      explicacao = :explicacao,
                      nivel_dificuldade = :nivel,
                      tags = :tags
                      WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':enunciado', $data['enunciado']);
            $stmt->bindParam(':alt_a', $data['alternativa_a']);
            $stmt->bindParam(':alt_b', $data['alternativa_b']);
            $stmt->bindParam(':alt_c', $data['alternativa_c']);
            $stmt->bindParam(':alt_d', $data['alternativa_d']);
            $stmt->bindParam(':alt_e', $data['alternativa_e']);
            $stmt->bindParam(':resposta', $data['resposta_correta']);
            $stmt->bindParam(':explicacao', $data['explicacao']);
            $stmt->bindParam(':nivel', $data['nivel_dificuldade']);
            $stmt->bindParam(':tags', $data['tags']);
            $stmt->bindParam(':id', $data['id']);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao editar questão');
            }
            break;

        case 'deletar':
            $id = $_POST['id'] ?? 0;

            $query = "DELETE FROM simulado_questoes WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao deletar questão');
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
