<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
requireAdmin();

$db = Database::getInstance();
$success = false;
$error = '';

/**
 * Validate and process a course cover image upload.
 *
 * Performs MIME type detection via finfo, extension validation,
 * size check, and returns the image as a base64 data URI.
 *
 * @param string $fieldName The $_FILES key for the upload field.
 * @return string|null Base64 data URI of the image, or null if no file was uploaded.
 * @throws Exception On any validation error.
 */
function processImageUpload(string $fieldName): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o tamanho máximo do formulário.',
            UPLOAD_ERR_PARTIAL    => 'O upload do arquivo foi feito parcialmente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário ausente.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo no disco.',
            UPLOAD_ERR_EXTENSION  => 'Uma extensão do PHP parou o upload do arquivo.',
        ];
        throw new Exception($uploadErrors[$_FILES[$fieldName]['error']] ?? 'Erro desconhecido no upload.');
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB (reduzido para base64)

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->file($_FILES[$fieldName]['tmp_name']);
    if (!in_array($detectedType, $allowedMimes)) {
        throw new Exception('Formato de imagem inválido. Use JPEG, PNG ou WebP.');
    }

    $extension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Extensão de arquivo não permitida. Use .jpg, .jpeg, .png ou .webp.');
    }

    if ($_FILES[$fieldName]['size'] > $maxSize) {
        throw new Exception('Imagem muito grande. Tamanho máximo: 2MB.');
    }

    // Ler conteúdo do arquivo e converter para base64
    $imageData = file_get_contents($_FILES[$fieldName]['tmp_name']);
    if ($imageData === false) {
        throw new Exception('Erro ao ler arquivo de imagem.');
    }

    // Retornar como data URI (funciona diretamente no src do img)
    return 'data:' . $detectedType . ';base64,' . base64_encode($imageData);
}

/**
 * Delete a course cover image file from disk (only for legacy file paths).
 *
 * Base64 data URIs are stored in the database and don't need file deletion.
 *
 * @param string|null $imagemCapa The image (path or base64 data URI).
 * @param bool $suppressErrors If true, uses @ to suppress unlink errors.
 */
function deleteCoverImage(?string $imagemCapa, bool $suppressErrors = false): void {
    if (!$imagemCapa) {
        return;
    }
    // Ignorar se for base64 (não há arquivo para deletar)
    if (str_starts_with($imagemCapa, 'data:')) {
        return;
    }
    // Deletar apenas arquivos físicos (imagens antigas)
    $fullPath = __DIR__ . '/..' . $imagemCapa;
    if (file_exists($fullPath)) {
        $suppressErrors ? @unlink($fullPath) : unlink($fullPath);
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_id']) && !isset($_POST['delete_id'])) {
    CSRFHelper::validateRequest(false);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);

    if (empty($titulo) || !$categoriaId) {
        $error = 'Título e categoria são obrigatórios';
    } else {
        try {
            $imagemCapa = processImageUpload('imagem_capa');

            $db->execute(
                "INSERT INTO cursos (titulo, descricao, categoria_id, imagem_capa, ativo) VALUES (?, ?, ?, ?, TRUE)",
                [$titulo, $descricao, $categoriaId, $imagemCapa]
            );
            $success = true;
        } catch (Exception $e) {
            $error = 'Erro ao cadastrar curso: ' . $e->getMessage();
        }
    }
}

if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
    CSRFHelper::validateRequest(false);
    $cursoId = (int)$_POST['edit_id'];
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $removerImagem = isset($_POST['remover_imagem']);

    if (empty($titulo) || !$categoriaId) {
        $error = 'Título e categoria são obrigatórios';
    } else {
        try {
            $cursoAtual = $db->fetchOne("SELECT imagem_capa FROM cursos WHERE id = ?", [$cursoId]);
            $imagemCapa = $cursoAtual['imagem_capa'];

            if ($removerImagem && $imagemCapa) {
                deleteCoverImage($imagemCapa);
                $imagemCapa = null;
            }

            $novaImagem = processImageUpload('imagem_capa');
            if ($novaImagem) {
                deleteCoverImage($imagemCapa, suppressErrors: true);
                $imagemCapa = $novaImagem;
            }

            $db->execute(
                "UPDATE cursos SET titulo = ?, descricao = ?, categoria_id = ?, imagem_capa = ? WHERE id = ?",
                [$titulo, $descricao, $categoriaId, $imagemCapa, $cursoId]
            );
            $success = 'Curso atualizado com sucesso!';

            header('Location: /admin/cursos.php?updated=1');
            exit;
        } catch (Exception $e) {
            $error = 'Erro ao atualizar curso: ' . $e->getMessage();
        }
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    CSRFHelper::validateRequest(false);
    $cursoId = (int)$_POST['delete_id'];
    try {
        $curso = $db->fetchOne("SELECT imagem_capa FROM cursos WHERE id = ?", [$cursoId]);
        if ($curso) {
            deleteCoverImage($curso['imagem_capa']);
        }

        $db->execute("UPDATE cursos SET ativo = FALSE WHERE id = ?", [$cursoId]);
        $success = 'Curso excluído com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao excluir curso: ' . $e->getMessage();
    }
}

$editingCurso = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editingCurso = $db->fetchOne("SELECT * FROM cursos WHERE id = ?", [(int)$_GET['edit']]);
}

if (isset($_GET['updated'])) {
    $success = 'Curso atualizado com sucesso!';
}

$cursos = $db->fetchAll("
    SELECT c.*, cat.nome as categoria_nome
    FROM cursos c
    JOIN categorias cat ON c.categoria_id = cat.id
    WHERE c.ativo = TRUE
    ORDER BY cat.nome, c.titulo
");

$categorias = $db->fetchAll("SELECT * FROM categorias WHERE ativo = TRUE ORDER BY nome");

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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Cursos</span>
        </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Cursos</h1>
                        <p class="text-gray-600 mt-2">Gerencie os cursos da plataforma</p>
                    </div>
                    <button onclick="toggleForm()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Novo Curso
                    </button>
                </div>
                
                <!-- Success/Error Messages -->
                ' . ($success ? '
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . (is_bool($success) ? 'Curso cadastrado com sucesso!' : htmlspecialchars($success)) . '</p>
                    </div>
                </div>' : '') . '
                
                ' . ($error ? '
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '

                <!-- Add/Edit Course Form -->
                <div id="courseForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" style="display: ' . ($editingCurso ? 'block' : 'none') . ';">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-' . ($editingCurso ? 'edit' : 'plus') . ' mr-2 text-blue-600"></i>
                        ' . ($editingCurso ? 'Editar Curso' : 'Novo Curso') . '
                    </h2>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        ' . CSRFHelper::getTokenField() . '
                        ' . ($editingCurso ? '<input type="hidden" name="edit_id" value="' . $editingCurso['id'] . '">' : '') . '
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título do Curso
                                </label>
                                <input type="text" 
                                       name="titulo" 
                                       required
                                       value="' . ($editingCurso ? htmlspecialchars($editingCurso['titulo']) : '') . '"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Digite o título do curso">
                    </div>
                    
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Categoria
                                </label>
                                <select name="categoria_id" 
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Selecione uma categoria</option>
                                    ' . implode('', array_map(function($categoria) use ($editingCurso) {
                                        $selected = ($editingCurso && $editingCurso['categoria_id'] == $categoria['id']) ? ' selected' : '';
                                        return '<option value="' . $categoria['id'] . '"' . $selected . '>' . htmlspecialchars($categoria['nome']) . '</option>';
                                    }, $categorias)) . '
                                </select>
                        </div>
                    </div>
                    
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição
                            </label>
                            <textarea name="descricao" 
                                      rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                      placeholder="Descreva o conteúdo do curso">' . ($editingCurso ? htmlspecialchars($editingCurso['descricao'] ?? '') : '') . '</textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-image mr-1 text-blue-600"></i>
                                Imagem de Capa (opcional)
                            </label>
                            
                            ' . ($editingCurso && $editingCurso['imagem_capa'] ? '
                            <!-- Imagem Atual -->
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <img src="' . htmlspecialchars($editingCurso['imagem_capa']) . '" 
                                             alt="Imagem atual" 
                                             class="w-20 h-20 object-cover rounded-lg border border-gray-300">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">Imagem atual</p>
                                            <p class="text-xs text-gray-500 mt-1">Faça upload de uma nova imagem para substituir</p>
                                        </div>
                                    </div>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               name="remover_imagem" 
                                               class="mr-2 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                        <span class="text-sm text-red-600 font-medium">Remover imagem</span>
                                    </label>
                                </div>
                            </div>' : '') . '
                            
                            <div class="flex items-center space-x-4">
                                <label class="flex-1">
                                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors cursor-pointer">
                                        <input type="file" 
                                               name="imagem_capa" 
                                               id="imagem_capa"
                                               accept="image/jpeg,image/png,image/jpg,image/webp"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                               onchange="previewImage(this)">
                                        <div class="text-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                            <p class="text-sm text-gray-600">' . ($editingCurso && $editingCurso['imagem_capa'] ? 'Clique para substituir a imagem' : 'Clique ou arraste uma imagem') . '</p>
                                            <p class="text-xs text-gray-500 mt-1">JPEG, PNG ou WebP (máx. 5MB)</p>
                                        </div>
                                    </div>
                                </label>
                                <div id="preview-container" class="hidden">
                                    <img id="image-preview" src="" alt="Preview" class="w-32 h-32 object-cover rounded-lg border border-gray-200">
                                            </div>
                                        </div>
                                    </div>
                                    
                        <div class="flex items-center space-x-4">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Salvar
                                            </button>
                            
                            ' . ($editingCurso ? 
                            '<a href="/admin/cursos.php" 
                                class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </a>' : 
                            '<button type="button" 
                                    onclick="toggleForm()"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>') . '
                                    </div>
                                </form>
                            </div>

                <!-- Courses List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-600"></i>
                            Lista de Cursos
                        </h2>
                    </div>
                    
                    ' . (empty($cursos) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-book text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhum curso cadastrado</h3>
                        <p class="text-gray-500">Comece criando seu primeiro curso.</p>
                    </div>' : '
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Curso
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Categoria
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
                                ' . implode('', array_map(function($curso) {
                                    return '
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                                <i class="fas fa-book text-blue-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($curso['titulo']) . '</div>
                                                ' . ($curso['descricao'] ? '<div class="text-sm text-gray-500 truncate max-w-xs">' . htmlspecialchars(substr($curso['descricao'], 0, 50)) . (strlen($curso['descricao']) > 50 ? '...' : '') . '</div>' : '') . '
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            ' . htmlspecialchars($curso['categoria_nome']) . '
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ' . date('d/m/Y H:i', strtotime($curso['data_criacao'])) . '
                                                </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="/curso.php?id=' . $curso['id'] . '" 
                                               class="text-blue-600 hover:text-blue-900 transition-colors" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?edit=' . $curso['id'] . '" 
                                               class="text-yellow-600 hover:text-yellow-900 transition-colors" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/admin/aulas.php?curso=' . $curso['id'] . '" 
                                               class="text-green-600 hover:text-green-900 transition-colors" title="Gerenciar Aulas">
                                                <i class="fas fa-video"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm(\'Tem certeza que deseja excluir este curso?\');">
                                                ' . CSRFHelper::getTokenField() . '
                                                <input type="hidden" name="delete_id" value="' . $curso['id'] . '">
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                                    </div>
                                                </td>
                                </tr>';
                                }, $cursos)) . '
                                        </tbody>
                                    </table>
                    </div>') . '
    </div>

    <script>
                function toggleForm() {
                    const form = document.getElementById("courseForm");
                    form.style.display = form.style.display === "none" ? "block" : "none";
                }
                
                function previewImage(input) {
                    const preview = document.getElementById("image-preview");
                    const container = document.getElementById("preview-container");
                    
                    if (input.files && input.files[0]) {
                        const file = input.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        
                        // Verificar tamanho
                        if (file.size > maxSize) {
                            alert("Arquivo muito grande! O tamanho máximo permitido é 5MB.");
                            input.value = "";
                            container.classList.add("hidden");
                            return;
                        }
                        
                        // Verificar tipo
                        const allowedTypes = ["image/jpeg", "image/png", "image/jpg", "image/webp"];
                        if (!allowedTypes.includes(file.type)) {
                            alert("Formato de arquivo inválido! Use apenas JPEG, PNG ou WebP.");
                            input.value = "";
                            container.classList.add("hidden");
                            return;
                        }
                        
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            container.classList.remove("hidden");
                        }
                        
                        reader.readAsDataURL(file);
                    } else {
                        container.classList.add("hidden");
                    }
                }
                </script>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Cursos - Administração', $content, true, true);
?>