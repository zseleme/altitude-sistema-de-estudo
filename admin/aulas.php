<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
requireAdmin();

$db = Database::getInstance();
$success = false;
$error = '';
$editingAula = null;

// Processar edição
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $aulaId = (int)$_GET['edit'];
    $editingAula = $db->fetchOne("SELECT * FROM aulas WHERE id = ?", [$aulaId]);
    if (!$editingAula) {
        $error = 'Aula não encontrada';
    } else {
        // Buscar materiais complementares se existirem
        $materiais = $db->fetchAll("SELECT * FROM materiais_complementares WHERE aula_id = ? AND ativo = TRUE ORDER BY id", [$aulaId]);
        if (!empty($materiais)) {
            // Padronizar nome da coluna para cada material
            foreach ($materiais as &$material) {
                $material['url'] = $material['url_arquivo'];
            }
            $editingAula['materiais'] = $materiais;
        }
    }
}

// Processar formulário (criar ou editar)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    CSRFHelper::validateRequest(false);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $urlVideo = trim($_POST['url_video'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 1);
    $cursoId = (int)($_POST['curso_id'] ?? 0);
    $duracaoMinutos = (int)($_POST['duracao_minutos'] ?? 30);
    $aulaId = isset($_POST['aula_id']) ? (int)$_POST['aula_id'] : null;
    
    // Dados dos materiais (opcional)
    $materiais = $_POST['materiais'] ?? [];
    $materiaisValidos = [];
    
    // Validar materiais
    foreach ($materiais as $material) {
        $titulo = trim($material['titulo'] ?? '');
        $descricao = trim($material['descricao'] ?? '');
        $url = trim($material['url'] ?? '');
        $tipo = trim($material['tipo'] ?? '');
        
        if (!empty($titulo) && !empty($url) && !empty($tipo)) {
            $materiaisValidos[] = [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'url' => $url,
                'tipo' => $tipo
            ];
        }
    }
    
    if (empty($titulo) || empty($urlVideo) || !$cursoId) {
        $error = 'Título, URL do vídeo e curso são obrigatórios';
    } else {
        try {
            $db->beginTransaction();
            
            if ($aulaId) {
                // Editar aula existente
                $db->execute(
                    "UPDATE aulas SET titulo = ?, descricao = ?, url_video = ?, ordem = ?, curso_id = ?, duracao_minutos = ? WHERE id = ?",
                    [$titulo, $descricao, $urlVideo, $ordem, $cursoId, $duracaoMinutos, $aulaId]
                );
                $success = 'Aula atualizada com sucesso!';
            } else {
                // Criar nova aula
                $db->execute(
                    "INSERT INTO aulas (titulo, descricao, url_video, ordem, curso_id, duracao_minutos, ativo) VALUES (?, ?, ?, ?, ?, ?, TRUE)",
                    [$titulo, $descricao, $urlVideo, $ordem, $cursoId, $duracaoMinutos]
                );
                $aulaId = $db->lastInsertId();
                $success = 'Aula criada com sucesso!';
            }
            
            // Processar materiais se fornecidos
            if (!empty($materiaisValidos) && $aulaId) {
                // Remover materiais existentes
                $db->execute("DELETE FROM materiais_complementares WHERE aula_id = ?", [$aulaId]);
                
                // Inserir novos materiais
                foreach ($materiaisValidos as $material) {
                    $db->execute(
                        "INSERT INTO materiais_complementares (titulo, descricao, url_arquivo, tipo, aula_id, ativo) VALUES (?, ?, ?, ?, ?, TRUE)",
                        [$material['titulo'], $material['descricao'], $material['url'], $material['tipo'], $aulaId]
                    );
                }
                $success .= ' ' . count($materiaisValidos) . ' material(is) complementar(es) adicionado(s)!';
            }
            
            $db->commit();
            $editingAula = null; // Limpar edição
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Erro ao salvar aula: ' . $e->getMessage();
        }
    }
}

// Processar exclusão (POST com CSRF)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    CSRFHelper::validateRequest(false);
    $aulaId = (int)$_POST['delete_id'];
    try {
        $db->execute("UPDATE aulas SET ativo = FALSE WHERE id = ?", [$aulaId]);
        $success = 'Aula excluída com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao excluir aula: ' . $e->getMessage();
    }
}

// Buscar aulas
$cursoFiltro = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$whereClause = "WHERE a.ativo = TRUE";
$params = [];

if ($cursoFiltro) {
    $whereClause .= " AND a.curso_id = ?";
    $params[] = $cursoFiltro;
}

$aulas = $db->fetchAll("
    SELECT a.*, c.titulo as curso_titulo, cat.nome as categoria_nome,
           COUNT(m.id) as total_materiais
    FROM aulas a
    JOIN cursos c ON a.curso_id = c.id
    JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN materiais_complementares m ON a.id = m.aula_id AND m.ativo = TRUE
    $whereClause
    GROUP BY a.id, c.titulo, cat.nome
    ORDER BY c.titulo, a.ordem, a.titulo
", $params);

$cursos = $db->fetchAll("SELECT * FROM cursos WHERE ativo = TRUE ORDER BY titulo");

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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Administração</span>
            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Aulas</span>
        </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Aulas</h1>
                        <p class="text-gray-600 mt-2">Gerencie as aulas dos cursos</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="showYoutubeImportModal()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fab fa-youtube mr-2"></i>
                            Importar Playlist do YouTube
                        </button>
                        <button onclick="toggleForm()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Nova Aula
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                ' . ($success ? '
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . htmlspecialchars($success) . '</p>
                    </div>
                </div>' : '') . '
                
                ' . ($error ? '
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '

                <!-- YouTube Import Modal -->
                <div id="youtubeImportModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                    <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <!-- Modal Header -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-2xl font-bold text-gray-900">
                                    <i class="fab fa-youtube text-red-600 mr-2"></i>
                                    Importar Playlist do YouTube
                                </h2>
                                <button onclick="closeYoutubeImportModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <i class="fas fa-times text-2xl"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- Step 1: URL Input -->
                            <div id="step-url" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        URL da Playlist do YouTube
                                    </label>
                                    <input type="url"
                                           id="playlist-url"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                           placeholder="https://www.youtube.com/playlist?list=PLxxx...">
                                    <p class="mt-2 text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Cole a URL completa da playlist do YouTube
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Selecione o Curso
                                    </label>
                                    <select id="import-curso-id"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                                        <option value="">Selecione um curso</option>
                                        ' . implode('', array_map(function($curso) {
                                            return '<option value="' . $curso['id'] . '">' . htmlspecialchars($curso['titulo']) . '</option>';
                                        }, $cursos)) . '
                                    </select>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" id="include-durations" checked class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                    <label for="include-durations" class="ml-2 text-sm text-gray-700">
                                        Buscar duração dos vídeos (pode demorar mais)
                                    </label>
                                </div>

                                <div id="loading-status" class="hidden p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-3"></div>
                                        <p class="text-blue-700 text-sm">Buscando vídeos da playlist...</p>
                                    </div>
                                </div>

                                <div id="error-status" class="hidden p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="flex items-start">
                                        <i class="fas fa-exclamation-circle text-red-600 mt-0.5 mr-2"></i>
                                        <p class="text-red-700 text-sm" id="error-message"></p>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button onclick="fetchPlaylistVideos()"
                                            class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                                        <i class="fas fa-search mr-2"></i>
                                        Buscar Vídeos
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Preview & Edit -->
                            <div id="step-preview" class="hidden space-y-4">
                                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
                                            <p class="text-green-700 font-medium">
                                                <span id="total-videos-found">0</span> vídeos encontrados na playlist
                                            </p>
                                        </div>
                                        <button onclick="backToUrlStep()" class="text-sm text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-arrow-left mr-1"></i>
                                            Voltar
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="select-all-videos" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500" checked>
                                        <span class="ml-2 text-sm font-medium text-gray-700">Selecionar todos</span>
                                    </label>
                                </div>

                                <div id="videos-list" class="space-y-3 max-h-96 overflow-y-auto">
                                    <!-- Videos serão carregados aqui via JavaScript -->
                                </div>

                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                    <button onclick="closeYoutubeImportModal()"
                                            class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancelar
                                    </button>
                                    <button onclick="importSelectedVideos()"
                                            class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="fas fa-download mr-2"></i>
                                        Importar Selecionados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Lesson Form -->
                <div id="lessonForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" style="display: ' . ($editingAula ? 'block' : 'none') . ';">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-' . ($editingAula ? 'edit' : 'plus') . ' mr-2 text-blue-600"></i>
                        ' . ($editingAula ? 'Editar Aula' : 'Nova Aula') . '
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        ' . CSRFHelper::getTokenField() . '
                        ' . ($editingAula ? '<input type="hidden" name="aula_id" value="' . $editingAula['id'] . '">' : '') . '
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título da Aula
                                </label>
                                <input type="text" 
                                       name="titulo" 
                                       value="' . ($editingAula ? htmlspecialchars($editingAula['titulo']) : '') . '"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Digite o título da aula">
                    </div>
                    
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Curso
                                </label>
                                <select name="curso_id" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Selecione um curso</option>
                                    ' . implode('', array_map(function($curso) use ($editingAula) {
                                        $selected = $editingAula && $editingAula['curso_id'] == $curso['id'] ? 'selected' : '';
                                        return '<option value="' . $curso['id'] . '" ' . $selected . '>' . htmlspecialchars($curso['titulo']) . '</option>';
                                    }, $cursos)) . '
                                </select>
                        </div>
                    </div>
                    
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    URL do Vídeo
                                </label>
                                <input type="url" 
                                       name="url_video" 
                                       value="' . ($editingAula ? htmlspecialchars($editingAula['url_video']) : '') . '"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="https://www.youtube.com/... ou https://dropbox.com/...">
                                <p class="mt-2 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Suportado: <strong>YouTube</strong>, <strong>Vimeo</strong>, <strong>OneDrive</strong> e <strong>Dropbox</strong>
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    • YouTube: https://www.youtube.com/watch?v=... ou https://youtu.be/...<br>
                                    • Vimeo: https://vimeo.com/...<br>
                                    • OneDrive: https://1drv.ms/v/...<br>
                                    • Dropbox: https://dropbox.com/... ou https://dl.dropboxusercontent.com/...
                                </p>
                    </div>
                    
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ordem
                                </label>
                                <input type="number" 
                                       name="ordem" 
                                       value="' . ($editingAula ? $editingAula['ordem'] : '1') . '"
                                       min="1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="1">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Duração (minutos)
                                </label>
                                <input type="number" 
                                       name="duracao_minutos" 
                                       value="' . ($editingAula ? ($editingAula['duracao_minutos'] ?? 30) : '30') . '"
                                       min="1"
                                       max="300"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="30">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição
                            </label>
                            <textarea name="descricao" 
                                      rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                      placeholder="Descreva o conteúdo da aula">' . ($editingAula ? htmlspecialchars($editingAula['descricao']) : '') . '</textarea>
                </div>
                
                        <!-- Materiais Complementares (Opcional) -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-paperclip mr-2 text-green-600"></i>
                                    Materiais Complementares (Opcional)
                                </h4>
                                <button type="button" 
                                        onclick="addMaterial()"
                                        class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>
                                    Adicionar Material
                        </button>
                    </div>
                            
                            <div id="materiais-container">
                                <!-- Materiais existentes serão carregados aqui -->
                                </div>
                                
                            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-green-600 mt-1 mr-2"></i>
                                    <div class="text-sm text-green-700">
                                        <p class="font-medium mb-1">Materiais Complementares</p>
                                        <p>Adicione quantos materiais desejar: PDFs, documentos, links ou outros recursos que complementem a aula. Clique em "Adicionar Material" para incluir mais materiais.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        <div class="flex items-center space-x-4">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Salvar
                            </button>
                            
                            <button type="button" 
                                    onclick="toggleForm()"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                                        </button>
                            </div>
                        </form>
                </div>

                <!-- Filter by Course -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-filter mr-2 text-blue-600"></i>
                        Filtrar por Curso
                    </h3>
                    
                    <!-- Search Input -->
                    <div class="mb-4 relative">
                        <div class="relative">
                            <input type="text" 
                                   id="curso-search" 
                                   placeholder="Pesquisar curso... (Ctrl+K)" 
                                   class="w-full px-4 py-2.5 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <button id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 hidden">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Course Tags -->
                    <div id="curso-tags" class="flex flex-wrap gap-2">
                        <a href="?" 
                           data-curso-id="" 
                           data-curso-nome="Todos os Cursos"
                           class="curso-tag inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . (!$cursoFiltro ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') . ' transition-colors">
                            Todos os Cursos
                        </a>
                        ' . implode('', array_map(function($curso) use ($cursoFiltro) {
                            $active = $cursoFiltro == $curso['id'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200';
                            return '<a href="?curso=' . $curso['id'] . '" 
                                       data-curso-id="' . $curso['id'] . '" 
                                       data-curso-nome="' . htmlspecialchars($curso['titulo']) . '"
                                       class="curso-tag inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . $active . ' transition-colors">' . 
                                       htmlspecialchars($curso['titulo']) . '</a>';
                        }, $cursos)) . '
                    </div>
                    
                    <!-- No Results Message -->
                    <div id="no-results" class="hidden mt-4 p-4 bg-gray-50 rounded-lg text-center">
                        <i class="fas fa-search text-gray-400 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Nenhum curso encontrado</p>
                    </div>
                </div>
                
                <script>
                // Filtro de busca de cursos com autocomplete
                (function() {
                    const searchInput = document.getElementById("curso-search");
                    const clearButton = document.getElementById("clear-search");
                    const cursoTags = document.querySelectorAll(".curso-tag");
                    const noResults = document.getElementById("no-results");
                    
                    if (!searchInput) return;
                    
                    // Função para normalizar texto (remover acentos)
                    function normalizeText(text) {
                        return text.toLowerCase()
                            .normalize("NFD")
                            .replace(/[\u0300-\u036f]/g, "");
                    }
                    
                    // Função para filtrar cursos
                    function filterCursos() {
                        const searchTerm = normalizeText(searchInput.value.trim());
                        let visibleCount = 0;
                        
                        cursoTags.forEach(tag => {
                            const cursoNome = normalizeText(tag.getAttribute("data-curso-nome") || "");
                            
                            if (searchTerm === "" || cursoNome.includes(searchTerm)) {
                                tag.classList.remove("hidden");
                                visibleCount++;
                            } else {
                                tag.classList.add("hidden");
                            }
                        });
                        
                        // Mostrar/ocultar mensagem de "nenhum resultado"
                        if (visibleCount === 0) {
                            noResults.classList.remove("hidden");
                        } else {
                            noResults.classList.add("hidden");
                        }
                        
                        // Mostrar/ocultar botão de limpar
                        if (searchInput.value.trim() !== "") {
                            clearButton.classList.remove("hidden");
                        } else {
                            clearButton.classList.add("hidden");
                        }
                    }
                    
                    // Event listeners
                    searchInput.addEventListener("input", filterCursos);
                    
                    searchInput.addEventListener("keydown", function(e) {
                        // Enter - seleciona o primeiro curso visível
                        if (e.key === "Enter") {
                            e.preventDefault();
                            const firstVisible = Array.from(cursoTags).find(tag => !tag.classList.contains("hidden"));
                            if (firstVisible) {
                                firstVisible.click();
                            }
                        }
                        
                        // Escape - limpa o campo
                        if (e.key === "Escape") {
                            searchInput.value = "";
                            filterCursos();
                            searchInput.blur();
                        }
                    });
                    
                    clearButton.addEventListener("click", function() {
                        searchInput.value = "";
                        filterCursos();
                        searchInput.focus();
                    });
                    
                    // Auto-focus no input com atalho Ctrl+K ou Cmd+K
                    document.addEventListener("keydown", function(e) {
                        if ((e.ctrlKey || e.metaKey) && e.key === "k") {
                            e.preventDefault();
                            searchInput.focus();
                            searchInput.select();
                        }
                    });
                })();
                </script>
                
                <!-- Lessons List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-600"></i>
                            Lista de Aulas
                        </h2>
                    </div>
                    
                    ' . (empty($aulas) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-video text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma aula cadastrada</h3>
                        <p class="text-gray-500">Comece criando sua primeira aula.</p>
                    </div>' : '
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aula
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Curso
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ordem
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data de Criação
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                    </tr>
                                </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ' . implode('', array_map(function($aula) {
                                    return '
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                                <i class="fas fa-play text-red-600"></i>
                                        </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center flex-wrap gap-2">
                                                    <div class="text-sm font-medium text-gray-900 break-words">' . htmlspecialchars($aula['titulo']) . '</div>
                                                    ' . ($aula['total_materiais'] > 0 ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap"><i class="fas fa-paperclip mr-1"></i>' . $aula['total_materiais'] . ' Material(is)</span>' : '') . '
                                                </div>
                                                ' . ($aula['descricao'] ? '<div class="text-sm text-gray-500 truncate max-w-md mt-1">' . htmlspecialchars(substr($aula['descricao'], 0, 80)) . (strlen($aula['descricao']) > 80 ? '...' : '') . '</div>' : '') . '
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($aula['curso_titulo']) . '</div>
                                            <div class="text-sm text-gray-500">' . htmlspecialchars($aula['categoria_nome']) . '</div>
                                            </div>
                                        </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            ' . $aula['ordem'] . '
                                        </span>
                                        </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ' . date('d/m/Y H:i', strtotime($aula['data_criacao'])) . '
                                        </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="/aula.php?id=' . $aula['id'] . '" 
                                               class="text-blue-600 hover:text-blue-900 transition-colors" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?edit=' . $aula['id'] . '" 
                                               class="text-yellow-600 hover:text-yellow-900 transition-colors" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <form method="POST" class="inline" onsubmit="return confirm(\'Tem certeza que deseja excluir esta aula?\');">
                                                ' . CSRFHelper::getTokenField() . '
                                                <input type="hidden" name="delete_id" value="' . $aula['id'] . '">
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                </tr>';
                                }, $aulas)) . '
                                </tbody>
                            </table>
                    </div>') . '
    </div>

    <script>
    let materialIndex = 0;

    function toggleForm() {
        const form = document.getElementById("lessonForm");
        form.style.display = form.style.display === "none" ? "block" : "none";
        
        // Limpar formulário se não estiver editando
        if (form.style.display === "block" && !' . ($editingAula ? 'true' : 'false') . ') {
            form.querySelector("form").reset();
            // Limpar materiais dinâmicos
            document.getElementById("materiais-container").innerHTML = "";
            materialIndex = 0;
        }
    }
    
    function addMaterial(material = null) {
        const container = document.getElementById("materiais-container");
        const materialDiv = document.createElement("div");
        materialDiv.className = "material-item border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50";
        materialDiv.id = "material-" + materialIndex;
        
        materialDiv.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <h5 class="text-md font-medium text-gray-900">
                    <i class="fas fa-paperclip mr-2 text-green-600"></i>
                    Material ${materialIndex + 1}
                </h5>
                <button type="button" 
                        onclick="removeMaterial(${materialIndex})"
                        class="text-red-600 hover:text-red-800 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Título do Material
                    </label>
                    <input type="text" 
                           name="materiais[${materialIndex}][titulo]" 
                           value="${material ? material.titulo : ""}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                           placeholder="Ex: Slides da Aula, Exercícios, etc.">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo do Material
                    </label>
                    <select name="materiais[${materialIndex}][tipo]" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        <option value="">Selecione o tipo</option>
                        <option value="pdf" ${material && material.tipo === "pdf" ? "selected" : ""}>PDF</option>
                        <option value="doc" ${material && material.tipo === "doc" ? "selected" : ""}>Documento (DOC)</option>
                        <option value="ppt" ${material && material.tipo === "ppt" ? "selected" : ""}>Apresentação (PPT)</option>
                        <option value="video" ${material && material.tipo === "video" ? "selected" : ""}>Vídeo</option>
                        <option value="link" ${material && material.tipo === "link" ? "selected" : ""}>Link</option>
                        <option value="imagem" ${material && material.tipo === "imagem" ? "selected" : ""}>Imagem</option>
                        <option value="outro" ${material && material.tipo === "outro" ? "selected" : ""}>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    URL do Material
                </label>
                <input type="url" 
                       name="materiais[${materialIndex}][url]" 
                       value="${material ? material.url : ""}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                       placeholder="https://exemplo.com/material.pdf">
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Descrição do Material
                </label>
                <textarea name="materiais[${materialIndex}][descricao]" 
                          rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                          placeholder="Descreva o material complementar">${material ? material.descricao : ""}</textarea>
            </div>
        `;
        
        container.appendChild(materialDiv);
        materialIndex++;
    }
    
    function removeMaterial(index) {
        const materialDiv = document.getElementById("material-" + index);
        if (materialDiv) {
            materialDiv.remove();
        }
    }
    
    // Carregar materiais existentes se estiver editando
    ' . ($editingAula && isset($editingAula['materiais']) ? '
    document.addEventListener("DOMContentLoaded", function() {
        ' . json_encode($editingAula['materiais']) . '.forEach(function(material) {
            addMaterial(material);
        });
    });' : '') . '

    // ===== YouTube Playlist Import Functions =====

    let fetchedVideos = [];

    function showYoutubeImportModal() {
        document.getElementById("youtubeImportModal").classList.remove("hidden");
        document.getElementById("step-url").classList.remove("hidden");
        document.getElementById("step-preview").classList.add("hidden");
        document.getElementById("playlist-url").value = "";
        document.getElementById("error-status").classList.add("hidden");
        fetchedVideos = [];
    }

    function closeYoutubeImportModal() {
        document.getElementById("youtubeImportModal").classList.add("hidden");
    }

    function backToUrlStep() {
        document.getElementById("step-url").classList.remove("hidden");
        document.getElementById("step-preview").classList.add("hidden");
    }

    async function fetchPlaylistVideos() {
        const playlistUrl = document.getElementById("playlist-url").value.trim();
        const cursoId = document.getElementById("import-curso-id").value;
        const includeDurations = document.getElementById("include-durations").checked;

        // Validações
        if (!playlistUrl) {
            showError("Por favor, insira a URL da playlist");
            return;
        }

        if (!cursoId) {
            showError("Por favor, selecione um curso");
            return;
        }

        // Mostrar loading
        document.getElementById("loading-status").classList.remove("hidden");
        document.getElementById("error-status").classList.add("hidden");

        try {
            const response = await fetch("/api/importar_playlist_youtube.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    playlist_url: playlistUrl,
                    include_durations: includeDurations
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || "Erro ao buscar playlist");
            }

            fetchedVideos = data.videos;

            // Esconder loading e mostrar preview
            document.getElementById("loading-status").classList.add("hidden");
            document.getElementById("step-url").classList.add("hidden");
            document.getElementById("step-preview").classList.remove("hidden");

            // Atualizar contadores
            document.getElementById("total-videos-found").textContent = data.total;

            // Renderizar lista de vídeos
            renderVideosList(fetchedVideos);

        } catch (error) {
            document.getElementById("loading-status").classList.add("hidden");
            showError(error.message);
        }
    }

    function renderVideosList(videos) {
        const container = document.getElementById("videos-list");
        container.innerHTML = "";

        videos.forEach((video, index) => {
            const videoDiv = document.createElement("div");
            videoDiv.className = "border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors";
            videoDiv.innerHTML = `
                <div class="flex items-start gap-4">
                    <input type="checkbox"
                           class="video-checkbox w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500 mt-1"
                           data-index="${index}"
                           checked>
                    <img src="${video.thumbnail}"
                         alt="${escapeHtml(video.titulo)}"
                         class="w-32 h-20 object-cover rounded flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-medium text-gray-900 text-sm break-words">${escapeHtml(video.titulo)}</h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 whitespace-nowrap">
                                #${video.ordem}
                            </span>
                        </div>
                        ${video.descricao ? `<p class="text-xs text-gray-600 mt-1 line-clamp-2">${escapeHtml(video.descricao.substring(0, 150))}${video.descricao.length > 150 ? "..." : ""}</p>` : ""}
                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                            <span><i class="fas fa-clock mr-1"></i>${video.duracao_minutos || "N/A"} min</span>
                            <a href="${video.url_video}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-external-link-alt mr-1"></i>Ver no YouTube
                            </a>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(videoDiv);
        });

        // Adicionar evento para "selecionar todos"
        document.getElementById("select-all-videos").addEventListener("change", function(e) {
            document.querySelectorAll(".video-checkbox").forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    }

    async function importSelectedVideos() {
        const cursoId = document.getElementById("import-curso-id").value;
        const selectedVideos = [];

        document.querySelectorAll(".video-checkbox:checked").forEach(checkbox => {
            const index = parseInt(checkbox.dataset.index);
            selectedVideos.push(fetchedVideos[index]);
        });

        if (selectedVideos.length === 0) {
            showError("Selecione pelo menos um vídeo para importar");
            return;
        }

        if (!confirm(`Deseja importar ${selectedVideos.length} vídeo(s) para o curso selecionado?`)) {
            return;
        }

        // Mostrar loading na tela
        const loadingOverlay = document.createElement("div");
        loadingOverlay.id = "import-loading";
        loadingOverlay.className = "fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center";
        loadingOverlay.innerHTML = `
            <div class="bg-white rounded-lg p-8 max-w-md">
                <div class="flex items-center mb-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mr-4"></div>
                    <p class="text-lg font-medium text-gray-900">Importando vídeos...</p>
                </div>
                <p class="text-sm text-gray-600">Por favor, aguarde enquanto importamos os vídeos selecionados.</p>
            </div>
        `;
        document.body.appendChild(loadingOverlay);

        try {
            const response = await fetch("/api/importar_playlist_youtube.php", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    videos: selectedVideos,
                    curso_id: parseInt(cursoId)
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || "Erro ao importar vídeos");
            }

            // Remover loading
            document.body.removeChild(loadingOverlay);

            // Fechar modal e recarregar página
            closeYoutubeImportModal();

            // Mostrar mensagem de sucesso e recarregar
            alert(`${data.imported} vídeo(s) importado(s) com sucesso!${data.errors.length > 0 ? "\n\nAlguns erros ocorreram:\n" + data.errors.join("\n") : ""}`);
            window.location.reload();

        } catch (error) {
            document.body.removeChild(loadingOverlay);
            showError(error.message);
        }
    }

    function showError(message) {
        document.getElementById("error-message").textContent = message;
        document.getElementById("error-status").classList.remove("hidden");
    }

    function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }
    </script>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Aulas - Administração', $content, true, true);
?>