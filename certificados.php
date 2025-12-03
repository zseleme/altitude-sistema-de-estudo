<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Criar diretório para uploads se não existir
$uploadDir = __DIR__ . '/uploads/certificados';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Processar upload de certificado externo
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_external') {
    $titulo = trim($_POST['titulo'] ?? '');
    $instituicao = trim($_POST['instituicao'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $dataConclusao = $_POST['data_conclusao'] ?? '';
    $cargaHoraria = !empty($_POST['carga_horaria']) ? (int)$_POST['carga_horaria'] : null;
    $urlVerificacao = trim($_POST['url_verificacao'] ?? '');

    if (empty($titulo) || empty($instituicao) || empty($categoria)) {
        $error = 'Título, instituição e categoria são obrigatórios';
    } else {
        try {
            $arquivoCertificado = null;

            // Processar upload do arquivo
            if (isset($_FILES['arquivo_certificado']) && $_FILES['arquivo_certificado']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['arquivo_certificado'];
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception('Apenas arquivos PDF, JPG e PNG são permitidos');
                }

                if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                    throw new Exception('O arquivo deve ter no máximo 5MB');
                }

                $newFileName = 'cert_' . $userId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . '/' . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $arquivoCertificado = 'uploads/certificados/' . $newFileName;
                } else {
                    throw new Exception('Erro ao fazer upload do arquivo');
                }
            }

            $db->execute(
                "INSERT INTO certificados_externos (usuario_id, titulo, instituicao, categoria, descricao, data_conclusao, carga_horaria, arquivo_certificado, url_verificacao)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $titulo, $instituicao, $categoria, $descricao, $dataConclusao ?: null, $cargaHoraria, $arquivoCertificado, $urlVerificacao ?: null]
            );

            $success = 'Certificado adicionado com sucesso!';
        } catch (Exception $e) {
            $error = 'Erro ao adicionar certificado: ' . $e->getMessage();
        }
    }
}

// Processar exclusão de certificado externo
if (isset($_GET['delete_external']) && is_numeric($_GET['delete_external'])) {
    $certId = (int)$_GET['delete_external'];

    try {
        // Buscar arquivo para deletar
        $cert = $db->fetchOne("SELECT arquivo_certificado FROM certificados_externos WHERE id = ? AND usuario_id = ?", [$certId, $userId]);

        if ($cert) {
            // Deletar do banco
            $db->execute("DELETE FROM certificados_externos WHERE id = ? AND usuario_id = ?", [$certId, $userId]);

            // Deletar arquivo físico
            if ($cert['arquivo_certificado'] && file_exists(__DIR__ . '/' . $cert['arquivo_certificado'])) {
                unlink(__DIR__ . '/' . $cert['arquivo_certificado']);
            }

            $success = 'Certificado removido com sucesso!';
        }
    } catch (Exception $e) {
        $error = 'Erro ao remover certificado: ' . $e->getMessage();
    }
}

// Buscar cursos concluídos (100% das aulas concluídas)
$cursosConcluidos = $db->fetchAll("
    SELECT
        c.id,
        c.titulo,
        c.descricao,
        c.imagem_capa,
        cat.nome as categoria_nome,
        COUNT(DISTINCT a.id) as total_aulas,
        COUNT(DISTINCT pa.aula_id) as aulas_concluidas,
        MIN(pa.data_conclusao) as data_inicio,
        MAX(pa.data_conclusao) as data_conclusao,
        SUM(a.duracao_minutos) as carga_horaria_total
    FROM cursos c
    INNER JOIN categorias cat ON c.categoria_id = cat.id
    INNER JOIN aulas a ON a.curso_id = c.id AND a.ativo = TRUE
    LEFT JOIN progresso_aulas pa ON pa.aula_id = a.id AND pa.usuario_id = ? AND pa.concluida = TRUE
    WHERE c.ativo = TRUE
    GROUP BY c.id, c.titulo, c.descricao, c.imagem_capa, cat.nome
    HAVING COUNT(DISTINCT a.id) = COUNT(DISTINCT pa.aula_id) AND COUNT(DISTINCT a.id) > 0
    ORDER BY MAX(pa.data_conclusao) DESC
", [$userId]);

// Buscar certificados externos por categoria
$certificadosExternos = [
    'graduacao' => $db->fetchAll("SELECT * FROM certificados_externos WHERE usuario_id = ? AND categoria = 'graduacao' ORDER BY data_conclusao DESC", [$userId]),
    'pos_mba' => $db->fetchAll("SELECT * FROM certificados_externos WHERE usuario_id = ? AND categoria = 'pos_mba' ORDER BY data_conclusao DESC", [$userId]),
    'extensao' => $db->fetchAll("SELECT * FROM certificados_externos WHERE usuario_id = ? AND categoria = 'extensao' ORDER BY data_conclusao DESC", [$userId]),
    'curso_livre' => $db->fetchAll("SELECT * FROM certificados_externos WHERE usuario_id = ? AND categoria = 'curso_livre' ORDER BY data_conclusao DESC", [$userId])
];

$categoriaLabels = [
    'graduacao' => 'Graduação',
    'pos_mba' => 'Pós-Graduação / MBA',
    'extensao' => 'Extensão',
    'curso_livre' => 'Cursos Livres'
];

$categoriaIcons = [
    'graduacao' => 'fa-graduation-cap',
    'pos_mba' => 'fa-user-graduate',
    'extensao' => 'fa-book-open',
    'curso_livre' => 'fa-certificate'
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Certificados</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Meus Certificados</h1>
                        <p class="text-gray-600 mt-2">Certificados da plataforma e externos</p>
                    </div>
                    <button onclick="toggleExternalForm()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Adicionar Certificado Externo
                    </button>
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

                <!-- Add External Certificate Form -->
                <div id="externalCertForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" style="display: none;">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-plus-circle mr-2 text-blue-600"></i>
                        Adicionar Certificado Externo
                    </h2>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="add_external">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título do Certificado *
                                </label>
                                <input type="text"
                                       name="titulo"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ex: Curso de Python Avançado">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Instituição/Plataforma *
                                </label>
                                <input type="text"
                                       name="instituicao"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ex: Udemy, Coursera, Universidade">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Categoria *
                                </label>
                                <select name="categoria"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Selecione...</option>
                                    <option value="graduacao">Graduação</option>
                                    <option value="pos_mba">Pós-Graduação / MBA</option>
                                    <option value="extensao">Extensão</option>
                                    <option value="curso_livre">Curso Livre</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Data de Conclusão
                                </label>
                                <input type="date"
                                       name="data_conclusao"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Carga Horária (horas)
                                </label>
                                <input type="number"
                                       name="carga_horaria"
                                       min="1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Ex: 40">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição
                            </label>
                            <textarea name="descricao"
                                      rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                      placeholder="Descrição opcional sobre o certificado..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Arquivo do Certificado (PDF, JPG, PNG - máx 5MB)
                                </label>
                                <input type="file"
                                       name="arquivo_certificado"
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    URL de Verificação
                                </label>
                                <input type="url"
                                       name="url_verificacao"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="https://...">
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 pt-2">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Salvar Certificado
                            </button>

                            <button type="button"
                                    onclick="toggleExternalForm()"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Cursos Concluídos na Plataforma -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-trophy mr-2 text-yellow-600"></i>
                            Cursos Concluídos na Plataforma
                        </h2>
                    </div>

                    ' . (empty($cursosConcluidos) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-graduation-cap text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhum curso concluído ainda</h3>
                        <p class="text-gray-500 mb-4">Complete todos as aulas de um curso para receber seu certificado!</p>
                        <a href="/cursos.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-book mr-2"></i>
                            Ver Cursos Disponíveis
                        </a>
                    </div>' : '
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ' . implode('', array_map(function($curso) {
                                $cargaHorasTotal = round($curso['carga_horaria_total'] / 60, 1);
                                return '
                            <div class="border border-gray-200 rounded-lg p-5 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-certificate text-2xl text-blue-600"></i>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>
                                        Concluído
                                    </span>
                                </div>

                                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . htmlspecialchars($curso['titulo']) . '</h3>
                                <p class="text-sm text-gray-600 mb-3">' . htmlspecialchars($curso['categoria_nome']) . '</p>

                                <div class="space-y-2 text-sm text-gray-600 mb-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-check w-5 mr-2 text-gray-400"></i>
                                        <span>Concluído em ' . date('d/m/Y', strtotime($curso['data_conclusao'])) . '</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock w-5 mr-2 text-gray-400"></i>
                                        <span>' . $cargaHorasTotal . ' horas</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-video w-5 mr-2 text-gray-400"></i>
                                        <span>' . $curso['total_aulas'] . ' aulas</span>
                                    </div>
                                </div>

                                <a href="/api/gerar_certificado.php?curso_id=' . $curso['id'] . '" target="_blank" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-download mr-2"></i>
                                    Baixar Certificado
                                </a>
                            </div>';
                            }, $cursosConcluidos)) . '
                        </div>
                    </div>') . '
                </div>

                <!-- Certificados Externos por Categoria -->';

foreach ($categoriaLabels as $catKey => $catLabel) {
    $certs = $certificadosExternos[$catKey];
    $icon = $categoriaIcons[$catKey];

    $content .= '
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas ' . $icon . ' mr-2 text-blue-600"></i>
                            ' . $catLabel . '
                        </h2>
                    </div>

                    ' . (empty($certs) ? '
                    <div class="p-8 text-center">
                        <i class="fas ' . $icon . ' text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nenhum certificado de ' . strtolower($catLabel) . ' adicionado</p>
                    </div>' : '
                    <div class="p-6">
                        <div class="space-y-4">
                            ' . implode('', array_map(function($cert) use ($catKey) {
                                return '
                            <div class="border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-start gap-4">
                                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-award text-2xl text-purple-600"></i>
                                            </div>

                                            <div class="flex-1">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-1">' . htmlspecialchars($cert['titulo']) . '</h3>
                                                <p class="text-sm text-gray-600 mb-3">' . htmlspecialchars($cert['instituicao']) . '</p>

                                                ' . ($cert['descricao'] ? '
                                                <p class="text-sm text-gray-600 mb-3">' . htmlspecialchars($cert['descricao']) . '</p>' : '') . '

                                                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                                    ' . ($cert['data_conclusao'] ? '
                                                    <div class="flex items-center">
                                                        <i class="fas fa-calendar w-5 mr-2 text-gray-400"></i>
                                                        <span>' . date('d/m/Y', strtotime($cert['data_conclusao'])) . '</span>
                                                    </div>' : '') . '

                                                    ' . ($cert['carga_horaria'] ? '
                                                    <div class="flex items-center">
                                                        <i class="fas fa-clock w-5 mr-2 text-gray-400"></i>
                                                        <span>' . $cert['carga_horaria'] . ' horas</span>
                                                    </div>' : '') . '
                                                </div>

                                                <div class="flex gap-2 mt-4">
                                                    ' . ($cert['arquivo_certificado'] ? '
                                                    <a href="/' . htmlspecialchars($cert['arquivo_certificado']) . '"
                                                       target="_blank"
                                                       class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-200 transition-colors">
                                                        <i class="fas fa-file-pdf mr-1"></i>
                                                        Ver Certificado
                                                    </a>' : '') . '

                                                    ' . ($cert['url_verificacao'] ? '
                                                    <a href="' . htmlspecialchars($cert['url_verificacao']) . '"
                                                       target="_blank"
                                                       class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 text-xs font-medium rounded-lg hover:bg-green-200 transition-colors">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Verificar Autenticidade
                                                    </a>' : '') . '

                                                    <a href="?delete_external=' . $cert['id'] . '"
                                                       onclick="return confirm(\'Tem certeza que deseja remover este certificado?\')"
                                                       class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors">
                                                        <i class="fas fa-trash mr-1"></i>
                                                        Remover
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                            }, $certs)) . '
                        </div>
                    </div>') . '
                </div>';
}

$content .= '
                <script>
                function toggleExternalForm() {
                    const form = document.getElementById("externalCertForm");
                    form.style.display = form.style.display === "none" ? "block" : "none";
                }
                </script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Meus Certificados', $content, true, true);
?>
