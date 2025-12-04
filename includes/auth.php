<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['is_admin'] ?? false);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /home.php');
        exit;
    }
}

function login($email, $password) {
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT * FROM usuarios WHERE email = ? AND ativo = TRUE",
        [$email]
    );
    
    if ($user && password_verify($password, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? false;
        return true;
    }
    
    return false;
}

function logout() {
    session_destroy();
    header('Location: /');
    exit;
}

function getCategorias() {
    $db = Database::getInstance();
    return $db->fetchAll("SELECT * FROM categorias WHERE ativo = TRUE ORDER BY nome");
}

function getCursosByCategoria($categoriaId) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM cursos WHERE categoria_id = ? AND ativo = TRUE ORDER BY titulo",
        [$categoriaId]
    );
}

function getAulasByCurso($cursoId) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM aulas WHERE curso_id = ? AND ativo = TRUE ORDER BY ordem",
        [$cursoId]
    );
}

function getAulaById($id) {
    $db = Database::getInstance();
    $aula = $db->fetchOne(
        "SELECT a.*, c.titulo as curso_titulo, cat.nome as categoria_nome 
         FROM aulas a 
         JOIN cursos c ON a.curso_id = c.id 
         JOIN categorias cat ON c.categoria_id = cat.id 
         WHERE a.id = ? AND a.ativo = TRUE",
        [$id]
    );
    return $aula;
}

function getCursoById($id) {
    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT c.*, cat.nome as categoria_nome 
         FROM cursos c 
         JOIN categorias cat ON c.categoria_id = cat.id 
         WHERE c.id = ? AND c.ativo = TRUE",
        [$id]
    );
}

function getVideoInfo($url) {
    if (empty($url)) {
        return null;
    }
    
    // YouTube - múltiplos formatos
    $youtubePatterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/'
    ];
    
    foreach ($youtubePatterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return [
                'type' => 'youtube',
                'id' => $matches[1],
                'embed_url' => 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1'
            ];
        }
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return [
            'type' => 'vimeo',
            'id' => $matches[1],
            'embed_url' => 'https://player.vimeo.com/video/' . $matches[1]
        ];
    }
    
    // OneDrive - formatos: 1drv.ms/v/... ou onedrive.live.com
    if (preg_match('/1drv\.ms\/v\/[cs]\/([a-zA-Z0-9]+)\/([A-Za-z0-9_-]+)/', $url, $matches) || 
        preg_match('/onedrive\.live\.com\/.*resid=([A-Z0-9]+)(?:%21|!)(\d+)/', $url, $matches)) {
        
        // Extrair o ID do vídeo do OneDrive
        $videoId = isset($matches[2]) ? $matches[2] : '';
        $resId = isset($matches[1]) ? $matches[1] : '';
        
        // Construir URL de embed do OneDrive
        // Formato: https://onedrive.live.com/embed?resid=RESID&authkey=AUTHKEY
        if (!empty($videoId) && !empty($resId)) {
            $embedUrl = 'https://onedrive.live.com/embed?resid=' . $resId . '!' . $videoId . '&authkey=!PLACEHOLDER';
            
            // Se a URL original tem parâmetros, tentar extrair authkey
            if (preg_match('/authkey=([^&]+)/', $url, $authMatch)) {
                $embedUrl = str_replace('!PLACEHOLDER', urldecode($authMatch[1]), $embedUrl);
            } else {
                // Tentar construir embed sem authkey (pode não funcionar para vídeos privados)
                $embedUrl = str_replace('&authkey=!PLACEHOLDER', '', $embedUrl);
            }
            
            return [
                'type' => 'onedrive',
                'id' => $videoId,
                'embed_url' => $embedUrl
            ];
        }
    }
    
    // OneDrive - formato alternativo (link direto de compartilhamento)
    // Exemplo: https://1drv.ms/v/c/6ffbbfe204ee7e2c/IQR3qYFglE9LSr0T1MmaXyRwAfxUL9D0y2746RF0aM7do0U
    if (preg_match('/1drv\.ms\/v\/[cs]\/([a-f0-9]+)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
        $resId = $matches[1];
        $videoId = $matches[2];
        
        // Converter para formato de embed
        $embedUrl = 'https://onedrive.live.com/embed?resid=' . strtoupper($resId) . '!' . $videoId;
        
        return [
            'type' => 'onedrive',
            'id' => $videoId,
            'embed_url' => $embedUrl
        ];
    }
    
    // Dropbox - links de compartilhamento
    // Formatos: dropbox.com/s/..., dropbox.com/scl/..., dl.dropboxusercontent.com
    if (preg_match('/dropbox\.com\/(s|scl)\//', $url, $matches) || 
        preg_match('/dl\.dropboxusercontent\.com/', $url)) {
        
        // Converter para link de download direto se necessário
        $directUrl = $url;
        
        // Se for link de compartilhamento do dropbox.com, converter para dl.dropboxusercontent.com
        if (strpos($url, 'dropbox.com/s/') !== false || strpos($url, 'dropbox.com/scl/') !== false) {
            // Trocar www.dropbox.com por dl.dropboxusercontent.com
            $directUrl = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $url);
            $directUrl = str_replace('dropbox.com', 'dl.dropboxusercontent.com', $directUrl);
            
            // Remover parâmetro dl=0 se existir e adicionar raw=1 para download direto
            $directUrl = preg_replace('/[?&]dl=0/', '', $directUrl);
            
            // Se não tiver parâmetros, adicionar ?
            if (strpos($directUrl, '?') === false) {
                $directUrl .= '?raw=1';
            } else {
                $directUrl .= '&raw=1';
            }
        }
        
        // Extrair ID do arquivo
        preg_match('/\/([a-zA-Z0-9_-]+)\.([a-z0-9]+)(\?|$)/', $directUrl, $fileMatch);
        $fileId = isset($fileMatch[1]) ? $fileMatch[1] : substr(md5($url), 0, 10);
        
        return [
            'type' => 'dropbox',
            'id' => $fileId,
            'embed_url' => $directUrl,
            'is_direct' => true // Indica que é um link direto para <video>
        ];
    }
    
    // Se já é uma URL de embed, retorna como está
    if (strpos($url, 'youtube.com/embed/') !== false || 
        strpos($url, 'player.vimeo.com/video/') !== false ||
        strpos($url, 'onedrive.live.com/embed') !== false) {
        return [
            'type' => 'embed',
            'id' => '',
            'embed_url' => $url
        ];
    }
    
    return null;
}

// Manter compatibilidade com código existente
function getYouTubeVideoId($url) {
    $info = getVideoInfo($url);
    return $info && $info['type'] === 'youtube' ? $info['id'] : null;
}

// Funções de progresso do curso
function getProgressoCurso($cursoId, $usuarioId) {
    $db = Database::getInstance();
    
    // Buscar total de aulas do curso
    $totalAulas = $db->fetchOne(
        "SELECT COUNT(*) as total FROM aulas WHERE curso_id = ? AND ativo = TRUE",
        [$cursoId]
    )['total'];
    
    if ($totalAulas == 0) {
        return [
            'total_aulas' => 0,
            'aulas_concluidas' => 0,
            'progresso_percentual' => 0,
            'tempo_estudado' => 0
        ];
    }
    
    // Buscar aulas concluídas
    $aulasConcluidas = $db->fetchOne(
        "SELECT COUNT(*) as concluidas 
         FROM progresso_aulas pa 
         JOIN aulas a ON pa.aula_id = a.id 
         WHERE a.curso_id = ? AND pa.usuario_id = ? AND pa.concluida = TRUE AND a.ativo = TRUE",
        [$cursoId, $usuarioId]
    )['concluidas'];
    
    // Calcular progresso percentual
    $progressoPercentual = round(($aulasConcluidas / $totalAulas) * 100, 1);
    
    // Calcular tempo estudado baseado na duração real das aulas concluídas
    $tempoEstudado = 0;
    if ($aulasConcluidas > 0) {
        $aulasConcluidasIds = $db->fetchAll(
            "SELECT a.duracao_minutos 
             FROM progresso_aulas pa 
             JOIN aulas a ON pa.aula_id = a.id 
             WHERE a.curso_id = ? AND pa.usuario_id = ? AND pa.concluida = TRUE AND a.ativo = TRUE",
            [$cursoId, $usuarioId]
        );
        
        foreach ($aulasConcluidasIds as $aula) {
            $tempoEstudado += $aula['duracao_minutos'] ?? 30;
        }
    }
    
    return [
        'total_aulas' => $totalAulas,
        'aulas_concluidas' => $aulasConcluidas,
        'progresso_percentual' => $progressoPercentual,
        'tempo_estudado' => $tempoEstudado
    ];
}

function getProgressoAula($aulaId, $usuarioId) {
    $db = Database::getInstance();
    
    $progresso = $db->fetchOne(
        "SELECT * FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ?",
        [$usuarioId, $aulaId]
    );
    
    return $progresso ? $progresso['concluida'] : false;
}

function marcarAulaConcluida($aulaId, $usuarioId, $concluida = true) {
    $db = Database::getInstance();
    
    try {
        // Verificar se já existe progresso para esta aula
        $progresso = $db->fetchOne(
            "SELECT * FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ?",
            [$usuarioId, $aulaId]
        );
        
        if ($progresso) {
            // Atualizar progresso existente
            $db->execute(
                "UPDATE progresso_aulas SET concluida = ?, data_conclusao = ? WHERE usuario_id = ? AND aula_id = ?",
                [$concluida, $concluida ? date('Y-m-d H:i:s') : null, $usuarioId, $aulaId]
            );
        } else {
            // Criar novo progresso
            $db->execute(
                "INSERT INTO progresso_aulas (usuario_id, aula_id, concluida, data_conclusao) VALUES (?, ?, ?, ?)",
                [$usuarioId, $aulaId, $concluida, $concluida ? date('Y-m-d H:i:s') : null]
            );
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Funções de estatísticas
function getEstatisticasGerais() {
    $db = Database::getInstance();
    
    try {
        // Total de usuários
        $totalUsuarios = $db->fetchOne("SELECT COUNT(*) as total FROM usuarios")['total'];
        
        // Total de cursos
        $totalCursos = $db->fetchOne("SELECT COUNT(*) as total FROM cursos WHERE ativo = TRUE")['total'];
        
        // Total de aulas
        $totalAulas = $db->fetchOne("SELECT COUNT(*) as total FROM aulas WHERE ativo = TRUE")['total'];
        
        // Total de categorias
        $totalCategorias = $db->fetchOne("SELECT COUNT(*) as total FROM categorias")['total'];
        
        // Total de aulas concluídas (todos os usuários)
        $totalAulasConcluidas = $db->fetchOne("SELECT COUNT(*) as total FROM progresso_aulas WHERE concluida = TRUE")['total'];
        
        return [
            'total_usuarios' => $totalUsuarios,
            'total_cursos' => $totalCursos,
            'total_aulas' => $totalAulas,
            'total_categorias' => $totalCategorias,
            'total_aulas_concluidas' => $totalAulasConcluidas
        ];
    } catch (Exception $e) {
        return [
            'total_usuarios' => 0,
            'total_cursos' => 0,
            'total_aulas' => 0,
            'total_categorias' => 0,
            'total_aulas_concluidas' => 0
        ];
    }
}

function getEstatisticasUsuario($usuarioId) {
    $db = Database::getInstance();
    
    try {
        // Usar valor booleano compatível com o banco de dados
        $true = $db->getBoolTrue();
        
        // Cursos em que o usuário está inscrito (todos os cursos ativos)
        $cursosInscritos = $db->fetchOne("SELECT COUNT(*) as total FROM cursos WHERE ativo = $true")['total'] ?? 0;
        
        // Aulas concluídas pelo usuário
        $aulasConcluidas = $db->fetchOne(
            "SELECT COUNT(*) as total FROM progresso_aulas WHERE usuario_id = ? AND concluida = $true",
            [$usuarioId]
        )['total'] ?? 0;
        
        // Total de aulas disponíveis
        $totalAulas = $db->fetchOne("SELECT COUNT(*) as total FROM aulas WHERE ativo = $true")['total'] ?? 0;
        
        // Tempo total estudado (em minutos)
        $tempoEstudado = 0;
        if ($aulasConcluidas > 0) {
            $aulasComDuracao = $db->fetchAll(
                "SELECT a.duracao_minutos 
                 FROM progresso_aulas pa 
                 JOIN aulas a ON pa.aula_id = a.id 
                 WHERE pa.usuario_id = ? AND pa.concluida = $true AND a.ativo = $true",
                [$usuarioId]
            );
            
            foreach ($aulasComDuracao as $aula) {
                $tempoEstudado += $aula['duracao_minutos'] ?? 30;
            }
        }
        
        // Cursos com progresso
        $cursosComProgresso = $db->fetchOne(
            "SELECT COUNT(DISTINCT a.curso_id) as total 
             FROM progresso_aulas pa 
             JOIN aulas a ON pa.aula_id = a.id 
             WHERE pa.usuario_id = ? AND pa.concluida = $true AND a.ativo = $true",
            [$usuarioId]
        )['total'] ?? 0;
        
        // Streak de dias consecutivos (simplificado - dias com aulas concluídas)
        if ($db->isPostgreSQL()) {
            $streakDias = $db->fetchOne(
                "SELECT COUNT(DISTINCT DATE(data_conclusao)) as total 
                 FROM progresso_aulas 
                 WHERE usuario_id = ? AND concluida = TRUE AND data_conclusao >= CURRENT_DATE - INTERVAL '30 days'",
                [$usuarioId]
            )['total'] ?? 0;
        } else {
            $streakDias = $db->fetchOne(
                "SELECT COUNT(DISTINCT DATE(data_conclusao)) as total 
                 FROM progresso_aulas 
                 WHERE usuario_id = ? AND concluida = 1 AND data_conclusao >= DATE('now', '-30 days')",
                [$usuarioId]
            )['total'] ?? 0;
        }
        
        return [
            'cursos_inscritos' => (int)$cursosInscritos,
            'aulas_concluidas' => (int)$aulasConcluidas,
            'total_aulas' => (int)$totalAulas,
            'tempo_estudado' => (int)$tempoEstudado,
            'cursos_com_progresso' => (int)$cursosComProgresso,
            'streak_dias' => (int)$streakDias
        ];
    } catch (Exception $e) {
        return [
            'cursos_inscritos' => 0,
            'aulas_concluidas' => 0,
            'total_aulas' => 0,
            'tempo_estudado' => 0,
            'cursos_com_progresso' => 0,
            'streak_dias' => 0
        ];
    }
}

function pesquisarConteudo($query, $usuarioId = null) {
    $db = Database::getInstance();
    $resultados = [
        'cursos' => [],
        'aulas' => [],
        'total' => 0
    ];
    
    if (empty($query)) {
        return $resultados;
    }
    
    $searchTerm = "%$query%";
    
    try {
        // Buscar cursos
        $cursos = $db->fetchAll("
            SELECT c.*, cat.nome as categoria_nome 
            FROM cursos c 
            LEFT JOIN categorias cat ON c.categoria_id = cat.id 
            WHERE c.ativo = TRUE 
            AND (LOWER(c.titulo) LIKE LOWER(?) OR LOWER(c.descricao) LIKE LOWER(?))
            ORDER BY 
                CASE 
                    WHEN LOWER(c.titulo) LIKE LOWER(?) THEN 1
                    WHEN LOWER(c.descricao) LIKE LOWER(?) THEN 2
                    ELSE 3
                END,
                c.titulo
        ", [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        
        // Buscar aulas
        $aulas = $db->fetchAll("
            SELECT a.*, c.titulo as curso_titulo, c.id as curso_id, cat.nome as categoria_nome
            FROM aulas a 
            JOIN cursos c ON a.curso_id = c.id 
            LEFT JOIN categorias cat ON c.categoria_id = cat.id 
            WHERE c.ativo = TRUE 
            AND (LOWER(a.titulo) LIKE LOWER(?) OR LOWER(a.descricao) LIKE LOWER(?))
            ORDER BY 
                CASE 
                    WHEN LOWER(a.titulo) LIKE LOWER(?) THEN 1
                    WHEN LOWER(a.descricao) LIKE LOWER(?) THEN 2
                    ELSE 3
                END,
                a.titulo
        ", [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        
        // Adicionar progresso aos cursos se usuário estiver logado
        if ($usuarioId) {
            foreach ($cursos as &$curso) {
                $curso['progresso'] = getProgressoCurso($curso['id'], $usuarioId);
            }
        }
        
        $resultados = [
            'cursos' => $cursos,
            'aulas' => $aulas,
            'total' => count($cursos) + count($aulas)
        ];
        
    } catch (Exception $e) {
        error_log("Erro na pesquisa: " . $e->getMessage());
    }
    
    return $resultados;
}

function markdownToHtml($text) {
    if (empty($text)) {
        return '';
    }
    
    // Escapar HTML primeiro
    $text = htmlspecialchars($text);
    
    // Converter Markdown básico
    // Headers
    $text = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-bold text-gray-900 mt-4 mb-2">$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-bold text-gray-900 mt-4 mb-2">$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1 class="text-2xl font-bold text-gray-900 mt-4 mb-2">$1</h1>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-bold">$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong class="font-bold">$1</strong>', $text);
    
    // Italic
    $text = preg_replace('/\*(.+?)\*/', '<em class="italic">$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em class="italic">$1</em>', $text);
    
    // Code inline
    $text = preg_replace('/`(.+?)`/', '<code class="bg-gray-100 text-red-600 px-1 py-0.5 rounded text-sm font-mono">$1</code>', $text);
    
    // Links
    $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" class="text-blue-600 hover:text-blue-700 underline" target="_blank">$1</a>', $text);
    
    // Listas não ordenadas
    $text = preg_replace('/^\* (.+)$/m', '<li class="ml-4">• $1</li>', $text);
    $text = preg_replace('/^- (.+)$/m', '<li class="ml-4">• $1</li>', $text);
    
    // Listas ordenadas
    $text = preg_replace('/^\d+\. (.+)$/m', '<li class="ml-4">$1</li>', $text);
    
    // Quebras de linha
    $text = nl2br($text);
    
    return $text;
}
?>
