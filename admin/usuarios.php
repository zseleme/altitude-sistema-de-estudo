<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
requireAdmin();

$db = Database::getInstance();
$success = false;
$error = '';

// Handle POST requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    // Handle delete action
    if ($action === 'delete') {
        // Validate CSRF token
        CSRFHelper::validateRequest(false);

        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        if ($usuarioId == $_SESSION['user_id']) {
            $error = 'Você não pode excluir seu próprio usuário';
        } elseif ($usuarioId > 0) {
            try {
                $db->execute("UPDATE usuarios SET ativo = FALSE WHERE id = ?", [$usuarioId]);
                $success = 'Usuário excluído com sucesso!';
            } catch (Exception $e) {
                $error = 'Erro ao excluir usuário: ' . $e->getMessage();
            }
        } else {
            $error = 'ID de usuário inválido';
        }
    }
    // Handle toggle admin action
    elseif ($action === 'toggle_admin') {
        // Validate CSRF token
        CSRFHelper::validateRequest(false);

        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        if ($usuarioId == $_SESSION['user_id']) {
            $error = 'Você não pode alterar suas próprias permissões';
        } elseif ($usuarioId > 0) {
            try {
                $usuario = $db->fetchOne("SELECT is_admin FROM usuarios WHERE id = ?", [$usuarioId]);
                if ($usuario) {
                    $newAdminStatus = $usuario['is_admin'] ? 0 : 1;
                    $db->execute("UPDATE usuarios SET is_admin = ? WHERE id = ?", [$newAdminStatus, $usuarioId]);
                    $success = 'Permissões do usuário atualizadas com sucesso!';
                }
            } catch (Exception $e) {
                $error = 'Erro ao atualizar permissões: ' . $e->getMessage();
            }
        } else {
            $error = 'ID de usuário inválido';
        }
    }
    // Handle create user action
    else {
        // Validate CSRF token
        CSRFHelper::validateRequest(false);

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $isAdmin = isset($_POST['is_admin']) ? true : false;

        if (empty($nome) || empty($email) || empty($password)) {
            $error = 'Nome, email e senha são obrigatórios';
        } elseif ($password !== $confirmPassword) {
            $error = 'As senhas não coincidem';
        } elseif (strlen($password) < 12) {
            $error = 'A senha deve ter pelo menos 12 caracteres';
        } else {
            // Verificar se email já existe
            $existingUser = $db->fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
            if ($existingUser) {
                $error = 'Este email já está cadastrado';
            } else {
                try {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $db->execute(
                        "INSERT INTO usuarios (nome, email, senha, is_admin, ativo) VALUES (?, ?, ?, ?, TRUE)",
                        [$nome, $email, $hashedPassword, $isAdmin]
                    );
                    $success = true;
                } catch (Exception $e) {
                    $error = 'Erro ao cadastrar usuário: ' . $e->getMessage();
                }
            }
        }
    }
}

$usuarios = $db->fetchAll("
    SELECT * FROM usuarios 
    WHERE ativo = TRUE 
    ORDER BY nome
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Usuários</span>
        </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Usuários</h1>
                        <p class="text-gray-600 mt-2">Gerencie os usuários da plataforma</p>
                    </div>
                    <button onclick="toggleForm()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Novo Usuário
                    </button>
                </div>

                <!-- Success/Error Messages -->
                ' . ($success ? '
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . (is_bool($success) ? 'Usuário cadastrado com sucesso!' : htmlspecialchars($success)) . '</p>
                    </div>
                </div>' : '') . '
                
                ' . ($error ? '
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '

                <!-- Add User Form -->
                <div id="userForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" style="display: none;">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-plus mr-2 text-blue-600"></i>
                        Novo Usuário
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        ' . CSRFHelper::getTokenField() . '
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome Completo
                                </label>
                                <input type="text"
                                       name="nome"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Digite o nome completo">
                    </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email"
                                       name="email"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Digite o email">
                        </div>
                    </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Senha
                                </label>
                                <input type="password"
                                       name="password"
                                       required
                                       minlength="12"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Mínimo 12 caracteres">
                    </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirmar Senha
                                </label>
                                <input type="password"
                                       name="confirm_password"
                                       required
                                       minlength="12"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Confirme a senha">
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="is_admin" 
                                   id="is_admin"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_admin" class="ml-2 block text-sm text-gray-700">
                                Administrador
                            </label>
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

                <!-- Users List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-600"></i>
                            Lista de Usuários
                        </h2>
                    </div>
                    
                    ' . (empty($usuarios) ? '
                    <div class="p-8 text-center">
                        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhum usuário cadastrado</h3>
                        <p class="text-gray-500">Comece criando seu primeiro usuário.</p>
                    </div>' : '
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuário
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data de Cadastro
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                            </tr>
                                        </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ' . implode('', array_map(function($usuario) {
                                    $isCurrentUser = $usuario['id'] == $_SESSION['user_id'];
                                    return '
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center mr-4">
                                                <span class="text-white font-semibold text-sm">' . strtoupper(substr($usuario['nome'], 0, 1)) . '</span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($usuario['nome']) . '</div>
                                                ' . ($isCurrentUser ? '<div class="text-xs text-blue-600 font-medium">Você</div>' : '') . '
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ' . htmlspecialchars($usuario['email']) . '
                                                </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        ' . ($usuario['is_admin'] ? 
                                            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-crown mr-1"></i>
                                                Administrador
                                            </span>' : 
                                            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-user mr-1"></i>
                                                Usuário
                                            </span>') . '
                                                </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ' . date('d/m/Y H:i', strtotime($usuario['data_criacao'])) . '
                                                </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            ' . (!$isCurrentUser ? '
                                            <form method="POST" style="display: inline;" onsubmit="return confirm(\'Tem certeza que deseja alterar as permissões deste usuário?\')">
                                                <input type="hidden" name="action" value="toggle_admin">
                                                <input type="hidden" name="usuario_id" value="' . $usuario['id'] . '">
                                                ' . CSRFHelper::getTokenField() . '
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900 transition-colors" title="Alterar Permissões">
                                                    <i class="fas fa-user-shield"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm(\'Tem certeza que deseja excluir este usuário?\')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="usuario_id" value="' . $usuario['id'] . '">
                                                ' . CSRFHelper::getTokenField() . '
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>' : '
                                            <span class="text-gray-400 text-xs">Usuário atual</span>') . '
                                                    </div>
                                                </td>
                                </tr>';
                                }, $usuarios)) . '
                                        </tbody>
                                    </table>
                    </div>') . '
    </div>

    <script>
                function toggleForm() {
                    const form = document.getElementById("userForm");
                    form.style.display = form.style.display === "none" ? "block" : "none";
                }
                </script>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Usuários - Administração', $content, true, true);
?>