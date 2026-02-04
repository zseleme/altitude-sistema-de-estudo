<?php
/**
 * Authentication and session management.
 *
 * Configures secure session settings, provides login/logout,
 * role-based access control, and data retrieval helpers for
 * courses, lessons, progress, and search.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || $_SERVER['SERVER_PORT'] == 443
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    ini_set('session.gc_maxlifetime', '7200');
    ini_set('session.cookie_lifetime', '7200');

    session_start();

    // Regenerate session ID every 30 minutes to limit hijacking window
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    // Detect user-agent change as a session hijacking signal
    if (isset($_SESSION['user_id'])) {
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $currentUserAgent;
        } elseif ($_SESSION['user_agent'] !== $currentUserAgent) {
            session_unset();
            session_destroy();
            session_start();
        }

        // TODO: Optionally validate IP (may break for mobile users switching networks)
        // $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
        // if (!isset($_SESSION['user_ip'])) {
        //     $_SESSION['user_ip'] = $currentIP;
        // } elseif ($_SESSION['user_ip'] !== $currentIP) {
        //     session_unset();
        //     session_destroy();
        //     session_start();
        // }
    }
}

require_once __DIR__ . '/auto_install.php';
require_once __DIR__ . '/../config/database.php';

/** @return bool Whether a user is currently authenticated. */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/** @return int|null The current user's ID, or null if not logged in. */
function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/** @return bool Whether the current user has admin privileges. */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['is_admin'] ?? false);
}

/**
 * Redirect to login if not authenticated.
 * Also enforces mandatory password change for flagged accounts.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    $currentPage = basename($_SERVER['PHP_SELF']);
    if (($currentPage !== 'alterar_senha.php') && ($_SESSION['password_change_required'] ?? false)) {
        header('Location: /alterar_senha.php?required=1');
        exit;
    }
}

/** Redirect to home if the current user is not an admin. */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /home.php');
        exit;
    }
}

/**
 * Authenticate a user by email and password.
 *
 * @param string $email    The user's email address.
 * @param string $password The plaintext password to verify.
 * @return bool True on successful authentication.
 */
function login(string $email, string $password): bool {
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT * FROM usuarios WHERE email = ? AND ativo = TRUE",
        [$email]
    );

    if ($user && password_verify($password, $user['senha'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? false;
        $_SESSION['password_change_required'] = $user['password_change_required'] ?? false;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['created'] = time();

        return true;
    }

    return false;
}

/** Destroy the session, clear cookies, and redirect to the landing page. */
function logout(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    header('Location: /');
    exit;
}

/** @return array All active categories ordered by name. */
function getCategorias(): array {
    $db = Database::getInstance();
    return $db->fetchAll("SELECT * FROM categorias WHERE ativo = TRUE ORDER BY nome");
}

/** @return array Active courses for a given category. */
function getCursosByCategoria(int $categoriaId): array {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM cursos WHERE categoria_id = ? AND ativo = TRUE ORDER BY titulo",
        [$categoriaId]
    );
}

/** @return array Active lessons for a given course, ordered by position. */
function getAulasByCurso(int $cursoId): array {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM aulas WHERE curso_id = ? AND ativo = TRUE ORDER BY ordem",
        [$cursoId]
    );
}

/** @return array|null Lesson with course and category info, or null if not found. */
function getAulaById(int $id): ?array {
    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT a.*, c.titulo as curso_titulo, cat.nome as categoria_nome
         FROM aulas a
         JOIN cursos c ON a.curso_id = c.id
         JOIN categorias cat ON c.categoria_id = cat.id
         WHERE a.id = ? AND a.ativo = TRUE",
        [$id]
    );
}

/** @return array|null Course with category info, or null if not found. */
function getCursoById(int $id): ?array {
    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT c.*, cat.nome as categoria_nome
         FROM cursos c
         JOIN categorias cat ON c.categoria_id = cat.id
         WHERE c.id = ? AND c.ativo = TRUE",
        [$id]
    );
}

/**
 * Parse a video URL and return embed information.
 *
 * Supports YouTube, Vimeo, OneDrive, and Dropbox share links.
 * Dropbox links return 'is_direct' => true, indicating the embed_url
 * should be used in a <video> tag rather than an <iframe>.
 *
 * @param string $url The original video URL.
 * @return array|null Associative array with 'type', 'id', 'embed_url' keys, or null if unrecognized.
 */
function getVideoInfo(string $url): ?array {
    if (empty($url)) {
        return null;
    }

    // YouTube
    $youtubePatterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
    ];

    foreach ($youtubePatterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return [
                'type' => 'youtube',
                'id' => $matches[1],
                'embed_url' => 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1',
            ];
        }
    }

    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return [
            'type' => 'vimeo',
            'id' => $matches[1],
            'embed_url' => 'https://player.vimeo.com/video/' . $matches[1],
        ];
    }

    // OneDrive -- standard share format with resid
    if (preg_match('/1drv\.ms\/v\/[cs]\/([a-zA-Z0-9]+)\/([A-Za-z0-9_-]+)/', $url, $matches) ||
        preg_match('/onedrive\.live\.com\/.*resid=([A-Z0-9]+)(?:%21|!)(\d+)/', $url, $matches)) {

        $videoId = $matches[2] ?? '';
        $resId = $matches[1] ?? '';

        if (!empty($videoId) && !empty($resId)) {
            $embedUrl = 'https://onedrive.live.com/embed?resid=' . $resId . '!' . $videoId . '&authkey=!PLACEHOLDER';

            if (preg_match('/authkey=([^&]+)/', $url, $authMatch)) {
                $embedUrl = str_replace('!PLACEHOLDER', urldecode($authMatch[1]), $embedUrl);
            } else {
                $embedUrl = str_replace('&authkey=!PLACEHOLDER', '', $embedUrl);
            }

            return ['type' => 'onedrive', 'id' => $videoId, 'embed_url' => $embedUrl];
        }
    }

    // OneDrive -- alternate direct share format
    if (preg_match('/1drv\.ms\/v\/[cs]\/([a-f0-9]+)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
        return [
            'type' => 'onedrive',
            'id' => $matches[2],
            'embed_url' => 'https://onedrive.live.com/embed?resid=' . strtoupper($matches[1]) . '!' . $matches[2],
        ];
    }

    // Dropbox -- converts share links to direct download URLs for <video> playback
    if (preg_match('/dropbox\.com\/(s|scl)\//', $url) ||
        preg_match('/dl\.dropboxusercontent\.com/', $url)) {

        $directUrl = $url;

        if (strpos($url, 'dropbox.com/s/') !== false || strpos($url, 'dropbox.com/scl/') !== false) {
            $directUrl = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $url);
            $directUrl = str_replace('dropbox.com', 'dl.dropboxusercontent.com', $directUrl);
            $directUrl = preg_replace('/[?&]dl=0/', '', $directUrl);
            $directUrl .= (strpos($directUrl, '?') === false ? '?raw=1' : '&raw=1');
        }

        preg_match('/\/([a-zA-Z0-9_-]+)\.([a-z0-9]+)(\?|$)/', $directUrl, $fileMatch);
        $fileId = $fileMatch[1] ?? substr(md5($url), 0, 10);

        return [
            'type' => 'dropbox',
            'id' => $fileId,
            'embed_url' => $directUrl,
            'is_direct' => true,
        ];
    }

    // Already an embed URL -- pass through
    if (strpos($url, 'youtube.com/embed/') !== false ||
        strpos($url, 'player.vimeo.com/video/') !== false ||
        strpos($url, 'onedrive.live.com/embed') !== false) {
        return ['type' => 'embed', 'id' => '', 'embed_url' => $url];
    }

    return null;
}

/** @deprecated Use getVideoInfo() instead. Kept for backward compatibility. */
function getYouTubeVideoId(string $url): ?string {
    $info = getVideoInfo($url);
    return ($info && $info['type'] === 'youtube') ? $info['id'] : null;
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
        $cursosInscritos = $db->fetchOne(
            "SELECT COUNT(*) as total FROM cursos WHERE ativo = ?",
            [$true]
        )['total'] ?? 0;

        // Aulas concluídas pelo usuário
        $aulasConcluidas = $db->fetchOne(
            "SELECT COUNT(*) as total FROM progresso_aulas WHERE usuario_id = ? AND concluida = ?",
            [$usuarioId, $true]
        )['total'] ?? 0;

        // Total de aulas disponíveis
        $totalAulas = $db->fetchOne(
            "SELECT COUNT(*) as total FROM aulas WHERE ativo = ?",
            [$true]
        )['total'] ?? 0;

        // Tempo total estudado (em minutos)
        $tempoEstudado = 0;
        if ($aulasConcluidas > 0) {
            $aulasComDuracao = $db->fetchAll(
                "SELECT a.duracao_minutos
                 FROM progresso_aulas pa
                 JOIN aulas a ON pa.aula_id = a.id
                 WHERE pa.usuario_id = ? AND pa.concluida = ? AND a.ativo = ?",
                [$usuarioId, $true, $true]
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
             WHERE pa.usuario_id = ? AND pa.concluida = ? AND a.ativo = ?",
            [$usuarioId, $true, $true]
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
    
    // Links (only allow http/https protocols to prevent javascript: injection)
    $text = preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function($matches) {
        $label = $matches[1];
        $url = $matches[2];
        if (preg_match('/^https?:\/\//i', $url)) {
            return '<a href="' . $url . '" class="text-blue-600 hover:text-blue-700 underline" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        }
        return $label;
    }, $text);
    
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
