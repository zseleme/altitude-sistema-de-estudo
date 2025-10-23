<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance();
$success = false;
$error = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        $error = 'Nome da categoria é obrigatório';
    } else {
        try {
            $db->execute(
                "INSERT INTO categorias (nome, ativo) VALUES (?, TRUE)",
                [$nome]
            );
            $success = true;
        } catch (Exception $e) {
            $error = 'Erro ao cadastrar categoria: ' . $e->getMessage();
        }
    }
}

// Processar exclusão
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $categoriaId = (int)$_GET['delete'];
    try {
        $db->execute("UPDATE categorias SET ativo = FALSE WHERE id = ?", [$categoriaId]);
        $success = 'Categoria excluída com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao excluir categoria: ' . $e->getMessage();
    }
}

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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Categorias</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Categorias</h1>
                        <p class="text-gray-600 mt-2">Gerencie as categorias dos cursos</p>
                    </div>
                    <button onclick="toggleForm()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Nova Categoria
                    </button>
                </div>

                <!-- Success/Error Messages -->
                ' . ($success ? '
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . (is_bool($success) ? 'Categoria cadastrada com sucesso!' : htmlspecialchars($success)) . '</p>
                    </div>
                </div>' : '') . '
                
                ' . ($error ? '
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '

                <!-- Add Category Form -->
                <div id="categoryForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" style="display: none;">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-plus mr-2 text-blue-600"></i>
                        Nova Categoria
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nome da Categoria
                            </label>
                            <input type="text" 
                                   name="nome" 
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Digite o nome da categoria">
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

                <!-- Categories List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-600"></i>
                            Lista de Categorias
                        </h2>
                    </div>
                    
                    ' . (empty($categorias) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-folder text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma categoria cadastrada</h3>
                        <p class="text-gray-500">Comece criando sua primeira categoria.</p>
                    </div>' : '
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome
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
                                ' . implode('', array_map(function($categoria) {
                                    return '
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                                <i class="fas fa-folder text-blue-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($categoria['nome']) . '</div>
                                                <div class="text-sm text-gray-500">ID: ' . $categoria['id'] . '</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ' . date('d/m/Y H:i', strtotime($categoria['data_criacao'])) . '
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="/admin/cursos.php?categoria=' . $categoria['id'] . '" 
                                               class="text-blue-600 hover:text-blue-900 transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?delete=' . $categoria['id'] . '" 
                                               onclick="return confirm(\'Tem certeza que deseja excluir esta categoria?\')"
                                               class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>';
                                }, $categorias)) . '
                            </tbody>
                        </table>
                    </div>') . '
                </div>

                <script>
                function toggleForm() {
                    const form = document.getElementById("categoryForm");
                    form.style.display = form.style.display === "none" ? "block" : "none";
                }
                </script>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Categorias - Administração', $content, true, true);
?>