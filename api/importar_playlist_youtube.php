<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/encryption_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
requireAdmin();

header('Content-Type: application/json');

$db = Database::getInstance();

// Função para buscar a chave API do YouTube
function getYoutubeApiKey() {
    global $db;
    $config = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = 'youtube_api_key'");
    $encryptedKey = $config ? $config['valor'] : '';

    // Decrypt the key if it's encrypted
    if (!empty($encryptedKey)) {
        return EncryptionHelper::decrypt($encryptedKey);
    }

    return '';
}

// Função para extrair o ID da playlist de diferentes formatos de URL
function extractPlaylistId($url) {
    $patterns = [
        '/[?&]list=([^&]+)/',  // URL padrão: ?list=PLxxx
        '/youtube\.com\/playlist\?list=([^&]+)/',  // URL específica de playlist
        '/youtu\.be\/.*\?list=([^&]+)/',  // URL encurtada com lista
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }

    // Se já for um ID (sem URL)
    if (preg_match('/^[A-Za-z0-9_-]+$/', $url)) {
        return $url;
    }

    return null;
}

// Função para buscar vídeos da playlist via YouTube Data API
function fetchPlaylistVideos($playlistId, $apiKey) {
    $videos = [];
    $nextPageToken = '';
    $maxResults = 50; // YouTube permite até 50 resultados por página

    do {
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails&maxResults={$maxResults}&playlistId={$playlistId}&key={$apiKey}";

        if ($nextPageToken) {
            $url .= "&pageToken={$nextPageToken}";
        }

        $response = @file_get_contents($url);

        if ($response === false) {
            $error = error_get_last();
            throw new Exception("Erro ao buscar playlist do YouTube: " . ($error['message'] ?? 'Erro desconhecido'));
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new Exception("Erro da API do YouTube: " . $data['error']['message']);
        }

        if (!isset($data['items'])) {
            throw new Exception("Nenhum vídeo encontrado na playlist");
        }

        foreach ($data['items'] as $index => $item) {
            $snippet = $item['snippet'];
            $videoId = $item['contentDetails']['videoId'];

            // Pular vídeos privados ou excluídos
            if ($snippet['title'] === 'Private video' || $snippet['title'] === 'Deleted video') {
                continue;
            }

            $videos[] = [
                'video_id' => $videoId,
                'titulo' => $snippet['title'],
                'descricao' => $snippet['description'] ?? '',
                'thumbnail' => $snippet['thumbnails']['medium']['url'] ?? '',
                'url_video' => "https://www.youtube.com/watch?v={$videoId}",
                'ordem' => count($videos) + 1,
                'duracao_minutos' => 0, // Será calculado separadamente se necessário
                'published_at' => $snippet['publishedAt'] ?? ''
            ];
        }

        $nextPageToken = $data['nextPageToken'] ?? '';

    } while ($nextPageToken);

    return $videos;
}

// Função para buscar duração dos vídeos (opcional, requer chamada adicional)
function fetchVideosDurations($videoIds, $apiKey) {
    $durations = [];

    // Dividir em chunks de 50 vídeos (limite da API)
    $chunks = array_chunk($videoIds, 50);

    foreach ($chunks as $chunk) {
        $ids = implode(',', $chunk);
        $url = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id={$ids}&key={$apiKey}";

        $response = @file_get_contents($url);

        if ($response === false) {
            continue; // Ignorar erros na busca de duração
        }

        $data = json_decode($response, true);

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $videoId = $item['id'];
                $duration = $item['contentDetails']['duration'] ?? 'PT0S';
                $durations[$videoId] = parseDuration($duration);
            }
        }
    }

    return $durations;
}

// Função para converter duração ISO 8601 em minutos
function parseDuration($duration) {
    $interval = new DateInterval($duration);
    $minutes = ($interval->h * 60) + $interval->i;
    return max(1, $minutes); // Mínimo de 1 minuto
}

// Processar requisição
try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Validar CSRF token
        CSRFHelper::validateRequest();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['playlist_url'])) {
            throw new Exception("URL da playlist não fornecida");
        }

        $playlistUrl = trim($input['playlist_url']);
        $includeDurations = $input['include_durations'] ?? true;

        // Extrair ID da playlist
        $playlistId = extractPlaylistId($playlistUrl);

        if (!$playlistId) {
            throw new Exception("URL de playlist inválida. Use o formato: https://www.youtube.com/playlist?list=PLxxx");
        }

        // Buscar chave API
        $apiKey = getYoutubeApiKey();

        if (empty($apiKey)) {
            throw new Exception("Chave API do YouTube não configurada. Configure em Configurações de IA.");
        }

        // Buscar vídeos da playlist
        $videos = fetchPlaylistVideos($playlistId, $apiKey);

        if (empty($videos)) {
            throw new Exception("Nenhum vídeo encontrado na playlist");
        }

        // Buscar durações se solicitado
        if ($includeDurations) {
            $videoIds = array_column($videos, 'video_id');
            $durations = fetchVideosDurations($videoIds, $apiKey);

            // Atualizar vídeos com durações
            foreach ($videos as &$video) {
                if (isset($durations[$video['video_id']])) {
                    $video['duracao_minutos'] = $durations[$video['video_id']];
                }
            }
        }

        echo json_encode([
            'success' => true,
            'videos' => $videos,
            'total' => count($videos),
            'playlist_id' => $playlistId
        ]);

    } elseif ($method === 'PUT') {
        // Validar CSRF token
        CSRFHelper::validateRequest();

        // Importar vídeos para o banco de dados
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['videos']) || !isset($input['curso_id'])) {
            throw new Exception("Dados inválidos para importação");
        }

        $videos = $input['videos'];
        $cursoId = (int)$input['curso_id'];

        if (empty($videos)) {
            throw new Exception("Nenhum vídeo selecionado para importação");
        }

        if (!$cursoId) {
            throw new Exception("Curso não selecionado");
        }

        $db->beginTransaction();

        $importedCount = 0;
        $errors = [];

        foreach ($videos as $video) {
            try {
                // Validar dados obrigatórios
                if (empty($video['titulo']) || empty($video['url_video'])) {
                    $errors[] = "Vídeo sem título ou URL: " . ($video['titulo'] ?? 'sem título');
                    continue;
                }

                // Inserir aula
                $db->execute(
                    "INSERT INTO aulas (titulo, descricao, url_video, ordem, curso_id, duracao_minutos, ativo) VALUES (?, ?, ?, ?, ?, ?, TRUE)",
                    [
                        $video['titulo'],
                        $video['descricao'] ?? '',
                        $video['url_video'],
                        $video['ordem'] ?? 1,
                        $cursoId,
                        $video['duracao_minutos'] ?? 30
                    ]
                );

                $importedCount++;

            } catch (Exception $e) {
                $errors[] = "Erro ao importar '{$video['titulo']}': " . $e->getMessage();
            }
        }

        if ($importedCount > 0) {
            $db->commit();

            echo json_encode([
                'success' => true,
                'imported' => $importedCount,
                'total' => count($videos),
                'errors' => $errors
            ]);
        } else {
            $db->rollback();
            throw new Exception("Nenhum vídeo foi importado. Erros: " . implode(', ', $errors));
        }

    } else {
        throw new Exception("Método não permitido");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
