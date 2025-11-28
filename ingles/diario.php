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

// Processar adição/edição de entrada de diário
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $dataEntrada = $_POST['data_entrada'] ?? date('Y-m-d');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $humor = $_POST['humor'] ?? null;
    $tags = trim($_POST['tags'] ?? '');

    if (empty($conteudo)) {
        $error = 'O conteúdo do diário é obrigatório';
    } else {
        try {
            if ($action === 'edit' && $id) {
                // Atualizar entrada existente
                $db->execute(
                    "UPDATE ingles_diario SET conteudo = ?, humor = ?, tags = ?, data_atualizacao = CURRENT_TIMESTAMP
                     WHERE id = ? AND usuario_id = ?",
                    [$conteudo, $humor, $tags, $id, $userId]
                );
                $success = 'Entrada do diário atualizada com sucesso!';
            } else {
                // Criar nova entrada (ou atualizar se já existir para a data)
                $existing = $db->fetchOne("SELECT id FROM ingles_diario WHERE usuario_id = ? AND data_entrada = ?", [$userId, $dataEntrada]);

                if ($existing) {
                    $db->execute(
                        "UPDATE ingles_diario SET conteudo = ?, humor = ?, tags = ?, data_atualizacao = CURRENT_TIMESTAMP
                         WHERE id = ? AND usuario_id = ?",
                        [$conteudo, $humor, $tags, $existing['id'], $userId]
                    );
                    $success = 'Entrada do diário atualizada com sucesso!';
                } else {
                    $db->execute(
                        "INSERT INTO ingles_diario (usuario_id, data_entrada, conteudo, humor, tags)
                         VALUES (?, ?, ?, ?, ?)",
                        [$userId, $dataEntrada, $conteudo, $humor, $tags]
                    );
                    $success = 'Entrada do diário criada com sucesso!';
                }
            }
        } catch (Exception $e) {
            $error = 'Erro ao salvar entrada: ' . $e->getMessage();
        }
    }
}

// Processar exclusão
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db->execute("DELETE FROM ingles_diario WHERE id = ? AND usuario_id = ?", [$id, $userId]);
        $success = 'Entrada excluída com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao excluir entrada: ' . $e->getMessage();
    }
}

// Buscar entrada para edição
$editingEntry = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editingEntry = $db->fetchOne("SELECT * FROM ingles_diario WHERE id = ? AND usuario_id = ?", [$id, $userId]);
}

// Filtros
$mesAno = $_GET['mes'] ?? date('Y-m');
$searchQuery = trim($_GET['search'] ?? '');

// Buscar entradas do diário
$sql = "SELECT * FROM ingles_diario WHERE usuario_id = ?";
$params = [$userId];

if ($mesAno) {
    $sql .= " AND strftime('%Y-%m', data_entrada) = ?";
    $params[] = $mesAno;
}

if ($searchQuery) {
    $sql .= " AND (conteudo LIKE ? OR tags LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY data_entrada DESC";
$entradas = $db->fetchAll($sql, $params);

// Estatísticas
$totalEntradas = $db->fetchOne("SELECT COUNT(*) as total FROM ingles_diario WHERE usuario_id = ?", [$userId])['total'];
$sequenciaAtual = calcularSequencia($db, $userId);
$porHumor = $db->fetchAll("
    SELECT humor, COUNT(*) as total
    FROM ingles_diario
    WHERE usuario_id = ? AND humor IS NOT NULL
    GROUP BY humor
", [$userId]);

// Função para calcular sequência de dias consecutivos
function calcularSequencia($db, $userId) {
    $entradas = $db->fetchAll("
        SELECT data_entrada
        FROM ingles_diario
        WHERE usuario_id = ?
        ORDER BY data_entrada DESC
    ", [$userId]);

    if (empty($entradas)) {
        return 0;
    }

    $sequencia = 0;
    $dataAnterior = null;

    foreach ($entradas as $entrada) {
        $dataAtual = new DateTime($entrada['data_entrada']);

        if ($dataAnterior === null) {
            // Primeira entrada - verificar se é hoje ou ontem
            $hoje = new DateTime();
            $ontem = (new DateTime())->modify('-1 day');

            if ($dataAtual->format('Y-m-d') === $hoje->format('Y-m-d') ||
                $dataAtual->format('Y-m-d') === $ontem->format('Y-m-d')) {
                $sequencia = 1;
                $dataAnterior = $dataAtual;
            } else {
                break;
            }
        } else {
            // Verificar se é o dia anterior
            $diaEsperado = clone $dataAnterior;
            $diaEsperado->modify('-1 day');

            if ($dataAtual->format('Y-m-d') === $diaEsperado->format('Y-m-d')) {
                $sequencia++;
                $dataAnterior = $dataAtual;
            } else {
                break;
            }
        }
    }

    return $sequencia;
}

$humorLabels = [
    'otimo' => ['Ótimo', 'text-green-700', 'bg-green-100', 'fa-laugh-beam'],
    'bom' => ['Bom', 'text-blue-700', 'bg-blue-100', 'fa-smile'],
    'neutro' => ['Neutro', 'text-gray-700', 'bg-gray-100', 'fa-meh'],
    'ruim' => ['Ruim', 'text-orange-700', 'bg-orange-100', 'fa-frown'],
    'pessimo' => ['Péssimo', 'text-red-700', 'bg-red-100', 'fa-sad-tear']
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Diário</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">📔 Diário de Inglês</h1>
                    <p class="text-gray-600 mt-2">Pratique escrevendo em inglês todos os dias</p>
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total de Entradas</p>
                                <p class="text-2xl font-bold text-gray-900">' . $totalEntradas . '</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-book text-2xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Sequência Atual</p>
                                <p class="text-2xl font-bold text-gray-900">' . $sequenciaAtual . ' dias</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-fire text-2xl text-orange-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Este Mês</p>
                                <p class="text-2xl font-bold text-gray-900">' . count($entradas) . '</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-check text-2xl text-green-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Entry Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-' . ($editingEntry ? 'edit' : 'plus-circle') . ' mr-2 text-blue-600"></i>
                        ' . ($editingEntry ? 'Editar Entrada' : 'Nova Entrada de Diário') . '
                    </h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="' . ($editingEntry ? 'edit' : 'add') . '">
                        ' . ($editingEntry ? '<input type="hidden" name="id" value="' . $editingEntry['id'] . '">' : '') . '

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Data da Entrada
                                </label>
                                <input type="date"
                                       name="data_entrada"
                                       value="' . ($editingEntry ? $editingEntry['data_entrada'] : date('Y-m-d')) . '"
                                       ' . ($editingEntry ? 'readonly' : '') . '
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       max="' . date('Y-m-d') . '">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Como você está se sentindo?
                                </label>
                                <select name="humor"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Não informar</option>';

foreach ($humorLabels as $value => $data) {
    $selected = ($editingEntry && $editingEntry['humor'] === $value) ? 'selected' : '';
    $content .= '<option value="' . $value . '" ' . $selected . '>' . $data[0] . '</option>';
}

$content .= '
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Write about your day in English *
                            </label>
                            <textarea name="conteudo"
                                      required
                                      rows="8"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono"
                                      placeholder="Today I learned...">' . htmlspecialchars($editingEntry['conteudo'] ?? '') . '</textarea>
                            <p class="text-xs text-gray-500 mt-1">💡 Dica: Escreva sobre o que você aprendeu hoje, suas dificuldades ou qualquer pensamento em inglês.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tags (separadas por vírgula)
                            </label>
                            <input type="text"
                                   name="tags"
                                   value="' . htmlspecialchars($editingEntry['tags'] ?? '') . '"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Ex: study, motivation, progress">
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                ' . ($editingEntry ? 'Atualizar' : 'Salvar') . '
                            </button>
                            ' . ($editingEntry ? '
                            <a href="/ingles/diario.php"
                               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </a>' : '') . '
                        </div>
                    </form>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <form method="GET" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text"
                                   name="search"
                                   value="' . htmlspecialchars($searchQuery) . '"
                                   placeholder="Buscar no diário..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <input type="month"
                                   name="mes"
                                   value="' . htmlspecialchars($mesAno) . '"
                                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                        ' . ($searchQuery || $mesAno !== date('Y-m') ? '
                        <a href="/ingles/diario.php"
                           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-times"></i>
                        </a>' : '') . '
                    </form>
                </div>

                <!-- Diary Entries -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-book-open mr-2 text-blue-600"></i>
                            Minhas Entradas
                        </h2>
                    </div>

                    ' . (empty($entradas) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma entrada encontrada</h3>
                        <p class="text-gray-500">Comece escrevendo seu diário em inglês!</p>
                    </div>' : '
                    <div class="divide-y divide-gray-200">
                        ' . implode('', array_map(function($entrada) use ($humorLabels) {
                            $humor = $entrada['humor'];
                            $humorData = $humor ? $humorLabels[$humor] : null;

                            // Formatar data
                            $dataObj = new DateTime($entrada['data_entrada']);
                            $dataFormatada = strftime('%A, %d de %B de %Y', $dataObj->getTimestamp());

                            // Verificar se é hoje
                            $hoje = date('Y-m-d');
                            $isToday = $entrada['data_entrada'] === $hoje;

                            return '
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            ' . ucfirst($dataFormatada) . '
                                        </h3>
                                        ' . ($isToday ? '
                                        <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                            <i class="fas fa-star mr-1"></i>
                                            Hoje
                                        </span>' : '') . '
                                        ' . ($humorData ? '
                                        <span class="inline-flex items-center px-2 py-1 ' . $humorData[2] . ' ' . $humorData[1] . ' text-xs font-medium rounded">
                                            <i class="fas ' . $humorData[3] . ' mr-1"></i>
                                            ' . $humorData[0] . '
                                        </span>' : '') . '
                                    </div>
                                </div>
                            </div>

                            <div class="text-gray-700 whitespace-pre-wrap mb-4 p-4 bg-gray-50 rounded-lg font-mono text-sm">' . nl2br(htmlspecialchars($entrada['conteudo'])) . '</div>

                            <div class="flex flex-wrap items-center gap-3">
                                ' . ($entrada['tags'] ? '
                                <div class="flex flex-wrap gap-2">' .
                                    implode('', array_map(function($tag) {
                                        return '<span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                            <i class="fas fa-tag mr-1"></i>
                                            ' . htmlspecialchars(trim($tag)) . '
                                        </span>';
                                    }, explode(',', $entrada['tags']))) . '
                                </div>' : '') . '

                                <span class="text-xs text-gray-500 ml-auto">
                                    <i class="fas fa-clock mr-1"></i>
                                    Atualizado em ' . date('d/m/Y H:i', strtotime($entrada['data_atualizacao'])) . '
                                </span>
                            </div>

                            <div class="flex gap-2 mt-4">
                                <a href="?edit=' . $entrada['id'] . '"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-edit mr-1"></i>
                                    Editar
                                </a>

                                <a href="?delete=' . $entrada['id'] . '"
                                   onclick="return confirm(\'Tem certeza que deseja excluir esta entrada?\')"
                                   class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash mr-1"></i>
                                    Excluir
                                </a>
                            </div>
                        </div>';
                        }, $entradas)) . '
                    </div>') . '
                </div>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Diário de Inglês', $content, true, true);
?>
