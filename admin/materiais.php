<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance();
$success = false;
$error = '';

// Processar formulário
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $aulaId = (int)($_POST['aula_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = $_POST['tipo'] ?? 'outro';
    $urlArquivo = trim($_POST['url_arquivo'] ?? '');
    $nomeArquivo = trim($_POST['nome_arquivo'] ?? '');
    $tamanhoArquivo = (int)($_POST['tamanho_arquivo'] ?? 0);
    $ordem = (int)($_POST['ordem'] ?? 1);
    
    if (!$aulaId || !$titulo) {
        $error = 'Aula e título são obrigatórios';
    } elseif (!$urlArquivo) {
        $error = 'URL do arquivo é obrigatória';
    } else {
        try {
            $db->execute(
                "INSERT INTO materiais_complementares (aula_id, titulo, descricao, tipo, url_arquivo, nome_arquivo, tamanho_arquivo, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)",
                [$aulaId, $titulo, $descricao, $tipo, $urlArquivo, $nomeArquivo, $tamanhoArquivo, $ordem]
            );
            $success = true;
        } catch (Exception $e) {
            $error = 'Erro ao adicionar material: ' . $e->getMessage();
        }
    }
}

// Processar exclusão
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $materialId = (int)$_GET['delete'];
    try {
        $db->execute("UPDATE materiais_complementares SET ativo = FALSE WHERE id = ?", [$materialId]);
        $success = 'Material removido com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao remover material: ' . $e->getMessage();
    }
}

// Buscar materiais
$aulaFiltro = isset($_GET['aula']) ? (int)$_GET['aula'] : null;
$whereClause = "WHERE m.ativo = TRUE";
$params = [];

if ($aulaFiltro) {
    $whereClause .= " AND m.aula_id = ?";
    $params[] = $aulaFiltro;
}

$materiais = $db->fetchAll("
    SELECT m.*, a.titulo as aula_titulo, c.titulo as curso_titulo, cat.nome as categoria_nome
    FROM materiais_complementares m
    JOIN aulas a ON m.aula_id = a.id
    JOIN cursos c ON a.curso_id = c.id
    JOIN categorias cat ON c.categoria_id = cat.id
    $whereClause
    ORDER BY c.titulo, a.ordem, m.ordem, m.titulo
", $params);

$aulas = $db->fetchAll("
    SELECT a.*, c.titulo as curso_titulo, cat.nome as categoria_nome
    FROM aulas a
    JOIN cursos c ON a.curso_id = c.id
    JOIN categorias cat ON c.categoria_id = cat.id
    WHERE a.ativo = TRUE
    ORDER BY c.titulo, a.ordem, a.titulo
");

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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Materiais</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Materiais Complementares</h1>
                        <p class="text-gray-600 mt-2">Gerencie os materiais complementares das aulas</p>
                    </div>
                    <button onclick="toggleForm()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Novo Material
                    </button>
                </div>

                <!-- Success/Error Messages -->
                ' . ($success ? '
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . (is_bool($success) ? 'Material adicionado com sucesso!' : htmlspecialchars($success)) . '</p>
                    </div>
                </div>' : '') . '
                
                ' . ($error ? '
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '

                <!-- Add Material Form -->
                <div id="materialForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" style="display: none;">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-plus mr-2 text-blue-600"></i>
                        Novo Material
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título do Material
                                </label>
                                <input type="text" 
                                       name="titulo" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Digite o título do material">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Aula
                                </label>
                                <select name="aula_id" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Selecione uma aula</option>
                                    ' . implode('', array_map(function($aula) {
                                        return '<option value="' . $aula['id'] . '">' . htmlspecialchars($aula['curso_titulo'] . ' - ' . $aula['titulo']) . '</option>';
                                    }, $aulas)) . '
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo
                                </label>
                                <select name="tipo" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="pdf">PDF</option>
                                    <option value="video">Vídeo</option>
                                    <option value="imagem">Imagem</option>
                                    <option value="link">Link</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ordem
                                </label>
                                <input type="number" 
                                       name="ordem" 
                                       value="1"
                                       min="1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="1">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tamanho (bytes)
                                </label>
                                <input type="number" 
                                       name="tamanho_arquivo" 
                                       min="0"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="0">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    URL do Arquivo
                                </label>
                                <input type="url" 
                                       name="url_arquivo" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="https://exemplo.com/arquivo.pdf">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Arquivo
                                </label>
                                <input type="text" 
                                       name="nome_arquivo" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="arquivo.pdf">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição
                            </label>
                            <textarea name="descricao" 
                                      rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                      placeholder="Descreva o material complementar"></textarea>
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

                <!-- Filter by Lesson -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-filter mr-2 text-blue-600"></i>
                        Filtrar por Aula
                    </h3>
                    
                    <div class="flex flex-wrap gap-2">
                        <a href="?" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . (!$aulaFiltro ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') . ' transition-colors">
                            Todas as Aulas
                        </a>
                        ' . implode('', array_map(function($aula) use ($aulaFiltro) {
                            $active = $aulaFiltro == $aula['id'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200';
                            return '<a href="?aula=' . $aula['id'] . '" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . $active . ' transition-colors">' . htmlspecialchars($aula['curso_titulo'] . ' - ' . $aula['titulo']) . '</a>';
                        }, $aulas)) . '
                    </div>
                </div>

                <!-- Materials List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-600"></i>
                            Lista de Materiais
                        </h2>
                    </div>
                    
                    ' . (empty($materiais) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhum material cadastrado</h3>
                        <p class="text-gray-500">Comece adicionando seu primeiro material complementar.</p>
                    </div>' : '
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Material
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aula
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tamanho
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ' . implode('', array_map(function($material) {
                                    $tipoIcons = [
                                        'pdf' => 'fas fa-file-pdf text-red-600',
                                        'video' => 'fas fa-video text-blue-600',
                                        'imagem' => 'fas fa-image text-green-600',
                                        'link' => 'fas fa-link text-purple-600',
                                        'outro' => 'fas fa-file text-gray-600'
                                    ];
                                    
                                    $tipoLabels = [
                                        'pdf' => 'PDF',
                                        'video' => 'Vídeo',
                                        'imagem' => 'Imagem',
                                        'link' => 'Link',
                                        'outro' => 'Outro'
                                    ];
                                    
                                    $iconClass = $tipoIcons[$material['tipo']] ?? $tipoIcons['outro'];
                                    $tipoLabel = $tipoLabels[$material['tipo']] ?? 'Outro';
                                    
                                    $tamanhoFormatado = $material['tamanho_arquivo'] > 0 ? 
                                        ($material['tamanho_arquivo'] > 1024*1024 ? 
                                            round($material['tamanho_arquivo']/(1024*1024), 2) . ' MB' : 
                                            round($material['tamanho_arquivo']/1024, 2) . ' KB') : 
                                        '-';
                                    
                                    return '
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-4">
                                                <i class="' . $iconClass . '"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($material['titulo']) . '</div>
                                                ' . ($material['descricao'] ? '<div class="text-sm text-gray-500 truncate max-w-xs">' . htmlspecialchars(substr($material['descricao'], 0, 50)) . (strlen($material['descricao']) > 50 ? '...' : '') . '</div>' : '') . '
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($material['aula_titulo']) . '</div>
                                            <div class="text-sm text-gray-500">' . htmlspecialchars($material['curso_titulo']) . '</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            ' . $tipoLabel . '
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ' . $tamanhoFormatado . '
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="' . htmlspecialchars($material['url_arquivo']) . '" 
                                               target="_blank"
                                               class="text-blue-600 hover:text-blue-900 transition-colors" title="Abrir">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <a href="?delete=' . $material['id'] . '" 
                                               onclick="return confirm(\'Tem certeza que deseja excluir este material?\')"
                                               class="text-red-600 hover:text-red-900 transition-colors" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>';
                                }, $materiais)) . '
                            </tbody>
                        </table>
                    </div>') . '
                </div>

                <script>
                function toggleForm() {
                    const form = document.getElementById("materialForm");
                    form.style.display = form.style.display === "none" ? "block" : "none";
                }
                </script>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Materiais - Administração', $content, true, true);
?>