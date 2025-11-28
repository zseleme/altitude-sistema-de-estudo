<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Processar adição/edição de anotação
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $categoria = $_POST['categoria'] ?? null;
    $tags = trim($_POST['tags'] ?? '');

    if (empty($conteudo)) {
        $error = 'O conteúdo da anotação é obrigatório';
    } else {
        try {
            if ($action === 'edit' && $id) {
                // Atualizar anotação existente
                $db->execute(
                    "UPDATE ingles_anotacoes SET titulo = ?, conteudo = ?, categoria = ?, tags = ?, data_atualizacao = CURRENT_TIMESTAMP
                     WHERE id = ? AND usuario_id = ?",
                    [$titulo, $conteudo, $categoria, $tags, $id, $userId]
                );
                $success = 'Anotação atualizada com sucesso!';
            } else {
                // Criar nova anotação
                $db->execute(
                    "INSERT INTO ingles_anotacoes (usuario_id, titulo, conteudo, categoria, tags)
                     VALUES (?, ?, ?, ?, ?)",
                    [$userId, $titulo, $conteudo, $categoria, $tags]
                );
                $success = 'Anotação criada com sucesso!';
            }
        } catch (Exception $e) {
            $error = 'Erro ao salvar anotação: ' . $e->getMessage();
        }
    }
}

// Processar exclusão
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db->execute("DELETE FROM ingles_anotacoes WHERE id = ? AND usuario_id = ?", [$id, $userId]);
        $success = 'Anotação excluída com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao excluir anotação: ' . $e->getMessage();
    }
}

// Buscar anotação para edição
$editingNote = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editingNote = $db->fetchOne("SELECT * FROM ingles_anotacoes WHERE id = ? AND usuario_id = ?", [$id, $userId]);
}

// Filtros
$categoriaFiltro = $_GET['categoria'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

// Buscar anotações
$sql = "SELECT * FROM ingles_anotacoes WHERE usuario_id = ?";
$params = [$userId];

if ($categoriaFiltro) {
    $sql .= " AND categoria = ?";
    $params[] = $categoriaFiltro;
}

if ($searchQuery) {
    $sql .= " AND (titulo LIKE ? OR conteudo LIKE ? OR tags LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY data_atualizacao DESC";
$anotacoes = $db->fetchAll($sql, $params);

// Estatísticas
$totalAnotacoes = $db->fetchOne("SELECT COUNT(*) as total FROM ingles_anotacoes WHERE usuario_id = ?", [$userId])['total'];
$porCategoria = $db->fetchAll("
    SELECT categoria, COUNT(*) as total
    FROM ingles_anotacoes
    WHERE usuario_id = ? AND categoria IS NOT NULL
    GROUP BY categoria
", [$userId]);

$categoriaLabels = [
    'vocabulario' => 'Vocabulário',
    'gramatica' => 'Gramática',
    'expressoes' => 'Expressões',
    'pronuncia' => 'Pronúncia',
    'outros' => 'Outros'
];

$categoriaIcons = [
    'vocabulario' => 'fa-book',
    'gramatica' => 'fa-spell-check',
    'expressoes' => 'fa-comments',
    'pronuncia' => 'fa-microphone',
    'outros' => 'fa-bookmark'
];

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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Inglês</span>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Anotações</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">📝 Anotações de Inglês</h1>
                    <p class="text-gray-600 mt-2">Organize seus estudos de vocabulário, gramática e muito mais</p>
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

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total</p>
                                <p class="text-2xl font-bold text-gray-900">' . $totalAnotacoes . '</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-sticky-note text-2xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>';

foreach ($porCategoria as $stat) {
    $catKey = $stat['categoria'];
    $catLabel = $categoriaLabels[$catKey] ?? $catKey;
    $catIcon = $categoriaIcons[$catKey] ?? 'fa-bookmark';

    $content .= '
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">' . $catLabel . '</p>
                                <p class="text-2xl font-bold text-gray-900">' . $stat['total'] . '</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas ' . $catIcon . ' text-2xl text-purple-600"></i>
                            </div>
                        </div>
                    </div>';
}

$content .= '
                </div>

                <!-- Add/Edit Note Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-' . ($editingNote ? 'edit' : 'plus-circle') . ' mr-2 text-blue-600"></i>
                        ' . ($editingNote ? 'Editar Anotação' : 'Nova Anotação') . '
                    </h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="' . ($editingNote ? 'edit' : 'add') . '">
                        ' . ($editingNote ? '<input type="hidden" name="id" value="' . $editingNote['id'] . '">' : '') . '

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título (opcional)
                                </label>
                                <input type="text"
                                       name="titulo"
                                       value="' . htmlspecialchars($editingNote['titulo'] ?? '') . '"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ex: Phrasal Verbs - Get">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Categoria
                                </label>
                                <select name="categoria"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Sem categoria</option>';

foreach ($categoriaLabels as $value => $label) {
    $selected = ($editingNote && $editingNote['categoria'] === $value) ? 'selected' : '';
    $content .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
}

$content .= '
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Conteúdo *
                            </label>
                            <textarea name="conteudo"
                                      required
                                      rows="6"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                      placeholder="Escreva sua anotação aqui...">' . htmlspecialchars($editingNote['conteudo'] ?? '') . '</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tags (separadas por vírgula)
                            </label>
                            <input type="text"
                                   name="tags"
                                   value="' . htmlspecialchars($editingNote['tags'] ?? '') . '"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Ex: verbs, important, exam">
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                ' . ($editingNote ? 'Atualizar' : 'Salvar') . '
                            </button>
                            ' . ($editingNote ? '
                            <a href="/ingles/anotacoes.php"
                               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </a>' : '') . '
                        </div>
                    </form>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <form method="GET" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text"
                                   name="search"
                                   value="' . htmlspecialchars($searchQuery) . '"
                                   placeholder="Buscar anotações..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <select name="categoria"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">Todas as categorias</option>';

foreach ($categoriaLabels as $value => $label) {
    $selected = $categoriaFiltro === $value ? 'selected' : '';
    $content .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
}

$content .= '
                            </select>
                        </div>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                        ' . ($searchQuery || $categoriaFiltro ? '
                        <a href="/ingles/anotacoes.php"
                           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-times"></i>
                        </a>' : '') . '
                    </form>
                </div>

                <!-- Notes List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-600"></i>
                            Minhas Anotações
                        </h2>
                    </div>

                    ' . (empty($anotacoes) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-sticky-note text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma anotação encontrada</h3>
                        <p class="text-gray-500">Comece criando sua primeira anotação acima!</p>
                    </div>' : '
                    <div class="divide-y divide-gray-200">
                        ' . implode('', array_map(function($nota) use ($categoriaLabels, $categoriaIcons) {
                            $categoria = $nota['categoria'];
                            $catLabel = $categoria ? ($categoriaLabels[$categoria] ?? $categoria) : 'Sem categoria';
                            $catIcon = $categoria ? ($categoriaIcons[$categoria] ?? 'fa-bookmark') : 'fa-bookmark';

                            return '
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas ' . $catIcon . ' text-2xl text-blue-600"></i>
                                        </div>

                                        <div class="flex-1">
                                            ' . ($nota['titulo'] ? '
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">' . htmlspecialchars($nota['titulo']) . '</h3>' : '') . '

                                            <div class="text-sm text-gray-600 mb-3 whitespace-pre-wrap">' . nl2br(htmlspecialchars($nota['conteudo'])) . '</div>

                                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                                <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-700 rounded">
                                                    <i class="fas fa-folder mr-1"></i>
                                                    ' . $catLabel . '
                                                </span>

                                                ' . ($nota['tags'] ? '
                                                <div class="flex flex-wrap gap-2">' .
                                                    implode('', array_map(function($tag) {
                                                        return '<span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                                            <i class="fas fa-tag mr-1"></i>
                                                            ' . htmlspecialchars(trim($tag)) . '
                                                        </span>';
                                                    }, explode(',', $nota['tags']))) . '
                                                </div>' : '') . '

                                                <span class="text-xs">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Atualizado em ' . date('d/m/Y H:i', strtotime($nota['data_atualizacao'])) . '
                                                </span>
                                            </div>

                                            <div class="flex gap-2 mt-4">
                                                <a href="?edit=' . $nota['id'] . '"
                                                   class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-200 transition-colors">
                                                    <i class="fas fa-edit mr-1"></i>
                                                    Editar
                                                </a>

                                                <a href="?delete=' . $nota['id'] . '"
                                                   onclick="return confirm(\'Tem certeza que deseja excluir esta anotação?\')"
                                                   class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors">
                                                    <i class="fas fa-trash mr-1"></i>
                                                    Excluir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        }, $anotacoes)) . '
                    </div>') . '
                </div>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Anotações de Inglês', $content, true, true);
?>
