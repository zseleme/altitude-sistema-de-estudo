<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$aulaId = (int)($_GET['id'] ?? 0);
if (!$aulaId) {
    header('Location: /home.php');
    exit;
}

$aula = getAulaById($aulaId);
if (!$aula) {
    header('Location: /home.php');
    exit;
}

$curso = getCursoById($aula['curso_id']);
$aulas = getAulasByCurso($aula['curso_id']);
$categorias = getCategorias();
$videoInfo = getVideoInfo($aula['url_video']);

// Encontrar próxima aula
$proximaAula = null;
foreach ($aulas as $index => $a) {
    if ($a['id'] == $aulaId && isset($aulas[$index + 1])) {
        $proximaAula = $aulas[$index + 1];
        break;
    }
}

// Encontrar aula anterior
$aulaAnterior = null;
foreach ($aulas as $index => $a) {
    if ($a['id'] == $aulaId && $index > 0) {
        $aulaAnterior = $aulas[$index - 1];
        break;
    }
}

// Buscar progresso do curso e da aula atual
$progressoCurso = getProgressoCurso($curso['id'], $_SESSION['user_id']);
$aulaConcluida = getProgressoAula($aulaId, $_SESSION['user_id']);

// Buscar anotações do usuário para esta aula
$db = Database::getInstance();
$anotacao = $db->fetchOne("SELECT * FROM anotacoes WHERE usuario_id = ? AND aula_id = ?", [$_SESSION['user_id'], $aulaId]);

// Buscar materiais complementares da aula
$materiais = $db->fetchAll("SELECT * FROM materiais_complementares WHERE aula_id = ? AND ativo = TRUE ORDER BY ordem", [$aulaId]);

$content = '
                <!-- Breadcrumb -->
                <nav class="flex mb-6" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="/home.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                <i class="fas fa-home mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <a href="/curso.php?id=' . $curso['id'] . '" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">' . htmlspecialchars($curso['titulo']) . '</a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">' . htmlspecialchars($aula['titulo']) . '</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Lesson Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="flex-1">
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">' . htmlspecialchars($aula['titulo']) . '</h1>
                            <p class="text-sm md:text-base text-gray-600">Curso: <a href="/curso.php?id=' . $curso['id'] . '" class="text-blue-600 hover:text-blue-700">' . htmlspecialchars($curso['titulo']) . '</a></p>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2 text-sm text-gray-500">
                                <i class="fas fa-clock"></i>
                                <span>' . ($aula['duracao_minutos'] ?? 30) . ' min</span>
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 md:gap-8">
                    <!-- Video Player -->
                    <div class="lg:col-span-3 space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="aspect-video bg-gray-900 relative">
                                ' . ($aula['url_video'] && $videoInfo ? 
                                    // Verificar se é Dropbox (usa tag <video>)
                                    (isset($videoInfo['is_direct']) && $videoInfo['is_direct'] ? '
                                <video 
                                    controls 
                                    controlsList="nodownload"
                                    class="w-full h-full"
                                    style="position:absolute;top:0;left:0;width:100%;height:100%;">
                                    <source src="' . htmlspecialchars($videoInfo['embed_url']) . '" type="video/mp4">
                                    <p class="text-white text-center p-4">Seu navegador não suporta vídeo HTML5.</p>
                                </video>' : 
                                    // YouTube, Vimeo, OneDrive (usa iframe)
                                    '<iframe 
                                    src="' . htmlspecialchars($videoInfo['embed_url']) . '" 
                                    class="w-full h-full"
                                    frameborder="0" 
                                    allowfullscreen
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                                </iframe>') : '
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <i class="fas fa-video text-4xl mb-4 opacity-50"></i>
                                        <p class="text-lg opacity-75">Vídeo não disponível</p>
                                        ' . (!$videoInfo && $aula['url_video'] ? '<p class="text-sm opacity-50 mt-2">URL do vídeo inválida</p>' : '') . '
                                </div>
                                </div>') . '
                            </div>
                        </div>
                        
                        <!-- Lesson Actions -->
                        <div class="mt-4 md:mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <button id="toggle-aula-concluida" 
                                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg transition-colors ' . ($aulaConcluida ? 'bg-gray-600 text-white hover:bg-gray-700' : 'bg-green-600 text-white hover:bg-green-700') . '">
                                    <i class="fas fa-' . ($aulaConcluida ? 'undo' : 'check') . ' mr-2"></i>
                                    <span class="hidden sm:inline">' . ($aulaConcluida ? 'Marcar como Não Concluída' : 'Marcar como Concluída') . '</span>
                                    <span class="sm:hidden">' . ($aulaConcluida ? 'Não Concluída' : 'Concluída') . '</span>
                                </button>
                                
                                <button class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-bookmark mr-2"></i>
                                    Favoritar
                                    </button>
                                </div>
                            
                            <div class="flex items-center justify-center sm:justify-end space-x-2">
                                <button id="theater-mode-btn" class="p-2 text-gray-400 hover:text-purple-600 transition-colors" title="Modo teatro">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button id="share-video-btn" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" title="Compartilhar vídeo">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                                ' . ($aula['url_video'] ? '
                                <a href="' . htmlspecialchars($aula['url_video']) . '" target="_blank" class="p-2 text-gray-400 hover:text-green-600 transition-colors" title="Abrir vídeo original">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>' : '') . '
                            </div>
                        </div>
                        
                        <!-- Navigation Between Lessons -->
                        ' . (($aulaAnterior || $proximaAula) ? '
                        <div class="mt-6 w-full">
                            <div class="flex flex-col sm:flex-row gap-3 max-w-5xl">
                                ' . ($aulaAnterior ? '
                                <a href="/aula.php?id=' . $aulaAnterior['id'] . '" 
                                   class="flex-1 group flex items-center p-3 bg-white border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:shadow-md transition-all max-w-md">
                                    <div class="flex items-center min-w-0 w-full">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-chevron-left text-blue-600 text-sm"></i>
                            </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs text-gray-500 mb-0.5">Anterior</p>
                                            <p class="text-sm font-semibold text-gray-900 truncate group-hover:text-blue-600 transition-colors">' . htmlspecialchars($aulaAnterior['titulo']) . '</p>
                                        </div>
                                    </div>
                                </a>' : '') . '
                                
                                ' . ($proximaAula ? '
                                <a href="/aula.php?id=' . $proximaAula['id'] . '" 
                                   class="flex-1 group flex items-center p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl hover:from-blue-600 hover:to-blue-700 shadow-md hover:shadow-lg transition-all max-w-md">
                                    <div class="flex items-center min-w-0 w-full">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs text-blue-100 mb-0.5">Próxima</p>
                                            <p class="text-sm font-semibold text-white truncate">' . htmlspecialchars($proximaAula['titulo']) . '</p>
                                        </div>
                                        <div class="flex-shrink-0 w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center ml-3">
                                            <i class="fas fa-chevron-right text-white text-sm"></i>
                                        </div>
                                    </div>
                                </a>' : '') . '
                            </div>
                        </div>' : '') . '
                        
                        <!-- Lesson Description -->
                        ' . (!empty($aula['descricao']) ? '
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                            <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3">
                                <i class="fas fa-align-left mr-2 text-blue-600"></i>
                                Descrição da Aula
                            </h3>
                            <div class="text-sm md:text-base text-gray-700 leading-relaxed prose prose-sm max-w-none">' . markdownToHtml($aula['descricao']) . '</div>
                        </div>' : '') . '
                        
                        <!-- Student Notes -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-sticky-note mr-2 text-yellow-600"></i>
                                    Minhas Anotações
                                </h3>
                                <button id="edit-notes-btn" class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                                    <i class="fas fa-edit mr-2"></i>
                                    ' . ($anotacao ? 'Editar' : 'Adicionar') . '
                                </button>
                                        </div>
                                        
                            <div id="notes-display" class="' . ($anotacao ? '' : 'hidden') . '">
                                <div class="text-gray-700 leading-relaxed prose prose-sm max-w-none">' . ($anotacao ? markdownToHtml($anotacao['conteudo']) : '') . '</div>
                                ' . ($anotacao ? '<p class="text-xs text-gray-500 mt-4">Última atualização: ' . date('d/m/Y H:i', strtotime($anotacao['data_atualizacao'])) . '</p>' : '') . '
                                        </div>
                                        
                            <div id="notes-form" class="' . ($anotacao ? 'hidden' : '') . '">
                                <textarea id="notes-content" 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors font-mono text-sm"
                                          rows="10"
                                          placeholder="Digite suas anotações aqui... (Suporta Markdown)&#10;&#10;Exemplo:&#10;# Título&#10;**Negrito** *Itálico*&#10;- Item de lista&#10;[Link](https://exemplo.com)">' . ($anotacao ? htmlspecialchars($anotacao['conteudo']) : '') . '</textarea>
                                
                                <div class="mt-4 flex items-center justify-between">
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Suporta Markdown: **negrito**, *itálico*, # títulos, [links](url), etc.
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button id="cancel-notes-btn" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                            <i class="fas fa-times mr-2"></i>
                                            Cancelar
                                        </button>
                                        <button id="save-notes-btn" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                                            <i class="fas fa-save mr-2"></i>
                                            Salvar
                                        </button>
                                        </div>
                                    </div>
                                </div>
                                
                            <div id="notes-empty" class="' . ($anotacao ? 'hidden' : '') . ' text-center py-8 text-gray-400">
                                <i class="fas fa-sticky-note text-4xl mb-3"></i>
                                <p class="text-sm">Você ainda não tem anotações para esta aula.</p>
                                <p class="text-xs mt-1">Clique em "Adicionar" para começar.</p>
                            </div>
                        </div>
                        
                        <!-- Materiais Complementares -->
                        ' . (!empty($materiais) ? '
                        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-paperclip mr-2 text-purple-600"></i>
                                Materiais Complementares
                            </h3>
                            
                            <div class="space-y-3">
                                ' . implode('', array_map(function($material) {
                                    $iconMap = [
                                        'pdf' => ['fas fa-file-pdf', 'text-red-600', 'bg-red-50'],
                                        'doc' => ['fas fa-file-word', 'text-blue-600', 'bg-blue-50'],
                                        'ppt' => ['fas fa-file-powerpoint', 'text-orange-600', 'bg-orange-50'],
                                        'video' => ['fas fa-video', 'text-purple-600', 'bg-purple-50'],
                                        'link' => ['fas fa-link', 'text-green-600', 'bg-green-50'],
                                        'imagem' => ['fas fa-image', 'text-pink-600', 'bg-pink-50'],
                                        'outro' => ['fas fa-file', 'text-gray-600', 'bg-gray-50']
                                    ];
                                    
                                    $icon = $iconMap[$material['tipo']] ?? $iconMap['outro'];
                                    
                                    return '
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-all">
                                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 w-10 h-10 ' . $icon[2] . ' rounded-lg flex items-center justify-center">
                                            <i class="' . $icon[0] . ' ' . $icon[1] . '"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 truncate">' . htmlspecialchars($material['titulo']) . '</h4>
                                            ' . (!empty($material['descricao']) ? '<p class="text-xs text-gray-500 truncate mt-0.5">' . htmlspecialchars($material['descricao']) . '</p>' : '') . '
                                            ' . ($material['tamanho_arquivo'] ? '<p class="text-xs text-gray-400 mt-0.5">' . number_format($material['tamanho_arquivo'] / 1024, 2) . ' KB</p>' : '') . '
                                        </div>
                                    </div>
                                    <a href="' . htmlspecialchars($material['url_arquivo']) . '" 
                                       target="_blank"
                                       class="flex-shrink-0 ml-3 inline-flex items-center px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                        <i class="fas fa-download mr-1.5"></i>
                                        <span class="hidden sm:inline">Baixar</span>
                                    </a>
                                </div>';
                                }, $materiais)) . '
                            </div>
                        </div>' : '') . '
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Course Progress -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-chart-line mr-2 text-green-600"></i>
                                Progresso do Curso
                            </h3>
                            
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progresso Geral</span>
                                    <span>' . $progressoCurso['progresso_percentual'] . '%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: ' . $progressoCurso['progresso_percentual'] . '%"></div>
                                </div>
                            </div>
                            
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span>Aulas Concluídas</span>
                                    <span>' . $progressoCurso['aulas_concluidas'] . ' / ' . $progressoCurso['total_aulas'] . '</span>
                                        </div>
                                <div class="flex justify-between">
                                    <span>Tempo Estudado</span>
                                    <span>' . $progressoCurso['tempo_estudado'] . ' min</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Course Lessons -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-list mr-2 text-blue-600"></i>
                                    Aulas do Curso
                                </h3>
                            </div>
                            
                            <div id="lessons-container" class="max-h-96 overflow-y-auto">
                                ' . implode('', array_map(function($index, $aulaItem) use ($aula) {
                                    $isActive = $aulaItem['id'] == $aula['id'];
                                    $isConcluida = getProgressoAula($aulaItem['id'], $_SESSION['user_id']);
                                    return '
                                <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors relative ' . ($isActive ? 'bg-blue-50 border-l-4 border-l-blue-600 lesson-active' : ($isConcluida ? 'border-l-4 border-l-green-500' : '')) . '">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center ' . ($isActive ? 'bg-blue-600 text-white' : ($isConcluida ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600')) . '">
                                            <span class="text-sm font-semibold">' . ($index + 1) . '</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 truncate">' . htmlspecialchars($aulaItem['titulo']) . '</h4>
                                            <p class="text-xs ' . ($isConcluida ? 'text-green-600' : 'text-gray-500') . '">
                                                ' . ($isConcluida ? '<i class="fas fa-check-circle mr-1"></i>' : '') . ($aulaItem['duracao_minutos'] ?? '30') . ' min
                                            </p>
                                        </div>
                                        ' . ($isActive ? '
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-play text-blue-600"></i>
                                        </div>' : '
                                        <a href="/aula.php?id=' . $aulaItem['id'] . '" class="flex-shrink-0 text-gray-400 hover:text-blue-600 transition-colors">
                                            <i class="fas fa-play"></i>
                                        </a>') . '
                                    </div>
                                </div>';
                                }, array_keys($aulas), array_values($aulas))) . '
                            </div>
                        </div>
                        
                        <!-- Navigation -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-arrows-alt mr-2 text-gray-600"></i>
                                Navegação
                            </h3>
                            
                            <div class="space-y-3">
                                <a href="/curso.php?id=' . $curso['id'] . '" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Voltar ao Curso
                                </a>
                                
                                <a href="/home.php" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-home mr-2"></i>
                                    Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

    <style>
        /* Modo Teatro */
        body.theater-mode {
            overflow: hidden;
        }
        
        body.theater-mode::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9998;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .theater-active {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            z-index: 9999 !important;
            width: 90vw !important;
            max-width: 1400px !important;
            height: auto !important;
            max-height: 90vh !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            animation: zoomIn 0.3s ease-in-out;
        }
        
        .theater-active iframe,
        .theater-active video {
            width: 100% !important;
            height: 80vh !important;
            max-height: 800px !important;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        /* Fechar com ESC - hint visual */
        body.theater-mode::after {
            content: "Pressione ESC ou clique fora para sair";
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 10000;
            opacity: 0;
            animation: fadeInOut 4s ease-in-out;
        }
        
        @keyframes fadeInOut {
            0%, 100% {
                opacity: 0;
            }
            10%, 90% {
                opacity: 1;
            }
        }
    </style>

    <script>
                // Modo teatro
                const theaterModeBtn = document.getElementById("theater-mode-btn");
                const videoContainer = document.querySelector(".bg-white.rounded-xl.shadow-sm.overflow-hidden");
                let isTheaterMode = false;
                
                if (theaterModeBtn && videoContainer) {
                    theaterModeBtn.addEventListener("click", function() {
                        isTheaterMode = !isTheaterMode;
                        
                        if (isTheaterMode) {
                            // Ativar modo teatro
                            document.body.classList.add("theater-mode");
                            videoContainer.classList.add("theater-active");
                            this.querySelector("i").classList.remove("fa-expand");
                            this.querySelector("i").classList.add("fa-compress");
                            this.title = "Sair do modo teatro";
                            
                            // Scroll suave para o vídeo
                            videoContainer.scrollIntoView({ behavior: "smooth", block: "center" });
                } else {
                            // Desativar modo teatro
                            document.body.classList.remove("theater-mode");
                            videoContainer.classList.remove("theater-active");
                            this.querySelector("i").classList.remove("fa-compress");
                            this.querySelector("i").classList.add("fa-expand");
                            this.title = "Modo teatro";
                        }
                    });
                    
                    // Fechar com ESC
                    document.addEventListener("keydown", function(e) {
                        if (e.key === "Escape" && isTheaterMode) {
                            theaterModeBtn.click();
                        }
                    });
                    
                    // Fechar ao clicar fora do vídeo (no overlay)
                    document.addEventListener("click", function(e) {
                        if (isTheaterMode) {
                            // Verifica se o clique foi fora do container do vídeo
                            if (!videoContainer.contains(e.target) && !theaterModeBtn.contains(e.target)) {
                                theaterModeBtn.click();
                            }
                        }
                    });
                }
                
                // Scroll automático para a aula ativa na sidebar
                const lessonsContainer = document.getElementById("lessons-container");
                const activeLesson = document.querySelector(".lesson-active");
                
                if (lessonsContainer && activeLesson) {
                    // Aguarda um momento para garantir que o DOM está totalmente carregado
                    setTimeout(function() {
                        // Calcula a posição do elemento ativo
                        const containerRect = lessonsContainer.getBoundingClientRect();
                        const activeLessonRect = activeLesson.getBoundingClientRect();
                        const relativeTop = activeLessonRect.top - containerRect.top;
                        
                        // Scroll suave para centralizar a aula ativa
                        lessonsContainer.scrollTo({
                            top: lessonsContainer.scrollTop + relativeTop - (lessonsContainer.clientHeight / 2) + (activeLessonRect.height / 2),
                            behavior: "smooth"
                        });
                    }, 100);
                }
                
                // Compartilhar vídeo
                const shareVideoBtn = document.getElementById("share-video-btn");
                if (shareVideoBtn) {
                    shareVideoBtn.addEventListener("click", function() {
                        const videoUrl = "' . addslashes($aula['url_video']) . '";
                        
                        // Tentar usar a API de compartilhamento nativa
                        if (navigator.share) {
                            navigator.share({
                                title: "' . addslashes($aula['titulo']) . '",
                                text: "Confira esta aula: ' . addslashes($aula['titulo']) . '",
                                url: videoUrl
                            })
                            .then(() => console.log("Compartilhado com sucesso"))
                            .catch((error) => {
                                // Se falhar, copiar para clipboard
                                copyToClipboard(videoUrl);
                            });
                } else {
                            // Fallback: copiar para clipboard
                            copyToClipboard(videoUrl);
                        }
                    });
                }
                
                // Função para copiar para clipboard
                function copyToClipboard(text) {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text)
                            .then(() => {
                                showNotification("Link do vídeo copiado para a área de transferência!", "success");
                            })
                            .catch(() => {
                                fallbackCopyToClipboard(text);
                            });
                } else {
                        fallbackCopyToClipboard(text);
                    }
                }
                
                // Fallback para navegadores antigos
                function fallbackCopyToClipboard(text) {
                    const textArea = document.createElement("textarea");
                    textArea.value = text;
                    textArea.style.position = "fixed";
                    textArea.style.left = "-999999px";
                    textArea.style.top = "-999999px";
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    
                    try {
                        const successful = document.execCommand("copy");
                        if (successful) {
                            showNotification("Link do vídeo copiado para a área de transferência!", "success");
                        } else {
                            showNotification("Não foi possível copiar o link", "error");
                        }
                    } catch (err) {
                        showNotification("Erro ao copiar o link", "error");
                    }
                    
                    document.body.removeChild(textArea);
                }
                
                // Toggle aula concluída
                document.getElementById("toggle-aula-concluida").addEventListener("click", function() {
                    const button = this;
                    const aulaId = ' . $aulaId . ';
                    const isCurrentlyCompleted = ' . ($aulaConcluida ? 'true' : 'false') . ';
            const newStatus = !isCurrentlyCompleted;
            
                    // Desabilitar botão durante requisição
                    button.disabled = true;
                    button.innerHTML = "<i class=\"fas fa-spinner fa-spin mr-2\"></i>Salvando...";
                    
                    // Fazer requisição para API
                    fetch("/api/progresso.php", {
                        method: "POST",
                headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                },
                        body: "aula_id=" + aulaId + "&concluida=" + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                            // Atualizar botão
                            if (newStatus) {
                                button.className = "inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors";
                                button.innerHTML = "<i class=\"fas fa-undo mr-2\"></i>Marcar como Não Concluída";
                } else {
                                button.className = "inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors";
                                button.innerHTML = "<i class=\"fas fa-check mr-2\"></i>Marcar como Concluída";
                            }
                    
                    // Mostrar notificação
                            showNotification(data.message, newStatus ? "success" : "info");
                            
                            // Recarregar página para atualizar progresso
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                } else {
                            showNotification(data.message, "error");
                            // Restaurar botão original
                            button.disabled = false;
                            button.innerHTML = "<i class=\"fas fa-" + (isCurrentlyCompleted ? "undo" : "check") + " mr-2\"></i>" + (isCurrentlyCompleted ? "Marcar como Não Concluída" : "Marcar como Concluída");
                }
            })
            .catch(error => {
                        console.error("Erro:", error);
                        showNotification("Erro ao salvar progresso", "error");
                        // Restaurar botão original
                        button.disabled = false;
                        button.innerHTML = "<i class=\"fas fa-" + (isCurrentlyCompleted ? "undo" : "check") + " mr-2\"></i>" + (isCurrentlyCompleted ? "Marcar como Não Concluída" : "Marcar como Concluída");
            });
        });
                
                // Função para mostrar notificações
                function showNotification(message, type = "info") {
                    const notification = document.createElement("div");
                    notification.className = "fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm " + 
                        (type === "success" ? "bg-green-600" : type === "error" ? "bg-red-600" : "bg-blue-600");
                    notification.innerHTML = "<i class=\"fas fa-" + 
                        (type === "success" ? "check-circle" : type === "error" ? "exclamation-circle" : "info-circle") + 
                        " mr-2\"></i>" + message;
            
            document.body.appendChild(notification);
            
                    // Remover notificação após 3 segundos
            setTimeout(() => {
                        notification.remove();
                    }, 3000);
                }
                
                // Gerenciamento de Anotações
                const editNotesBtn = document.getElementById("edit-notes-btn");
                const cancelNotesBtn = document.getElementById("cancel-notes-btn");
                const saveNotesBtn = document.getElementById("save-notes-btn");
                const notesDisplay = document.getElementById("notes-display");
                const notesForm = document.getElementById("notes-form");
                const notesEmpty = document.getElementById("notes-empty");
                const notesContent = document.getElementById("notes-content");
                
                editNotesBtn.addEventListener("click", function() {
                    notesDisplay.classList.add("hidden");
                    notesEmpty.classList.add("hidden");
                    notesForm.classList.remove("hidden");
                    notesContent.focus();
                });
                
                cancelNotesBtn.addEventListener("click", function() {
                    notesForm.classList.add("hidden");
                    const hasNotes = notesDisplay.querySelector(".prose") && notesDisplay.querySelector(".prose").innerHTML.trim() !== "";
                    if (hasNotes) {
                        notesDisplay.classList.remove("hidden");
                    } else {
                        notesEmpty.classList.remove("hidden");
                    }
                });
                
                saveNotesBtn.addEventListener("click", function() {
                    const content = notesContent.value.trim();
                    
                    if (!content) {
                        showNotification("Por favor, digite suas anotações", "error");
                        return;
                    }
                    
                    saveNotesBtn.disabled = true;
                    saveNotesBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin mr-2\"></i>Salvando...";
                    
                    fetch("/api/anotacoes.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: "aula_id=" + ' . $aulaId . ' + "&conteudo=" + encodeURIComponent(content)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification("Anotações salvas com sucesso!", "success");
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification(data.error || "Erro ao salvar anotações", "error");
                            saveNotesBtn.disabled = false;
                            saveNotesBtn.innerHTML = "<i class=\"fas fa-save mr-2\"></i>Salvar";
                        }
                    })
                    .catch(error => {
                        console.error("Erro:", error);
                        showNotification("Erro ao salvar anotações", "error");
                        saveNotesBtn.disabled = false;
                        saveNotesBtn.innerHTML = "<i class=\"fas fa-save mr-2\"></i>Salvar";
                    });
                });
                </script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout(htmlspecialchars($aula['titulo']), $content, true, true);
?>