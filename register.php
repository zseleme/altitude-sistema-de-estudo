<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf_helper.php';

$error = '';
$success = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFHelper::validateRequest(false);
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($nome) || empty($email) || empty($password)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } else {
        $db = Database::getInstance();
        
        // Verificar se email já existe
        $existingUser = $db->fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
        if ($existingUser) {
            $error = 'Este email já está cadastrado';
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $db->execute(
                    "INSERT INTO usuarios (nome, email, senha, is_admin, ativo) VALUES (?, ?, ?, FALSE, TRUE)",
                    [$nome, $email, $hashedPassword]
                );
                $success = 'Conta criada com sucesso! Faça login para continuar.';
            } catch (Exception $e) {
                $error = 'Erro ao criar conta: ' . $e->getMessage();
            }
        }
    }
}

$content = '
    <div class="min-h-screen bg-gradient-to-br from-green-600 to-blue-700 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-white rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-mountain text-3xl text-green-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">Altitude</h2>
                <p class="text-green-100">Crie sua conta e comece a aprender</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-2xl p-8">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 text-center mb-2">
                        <i class="fas fa-user-plus mr-2 text-green-600"></i>
                        Criar Conta
                    </h3>
                </div>
                
                ' . ($error ? '<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '
                
                ' . ($success ? '<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . htmlspecialchars($success) . '</p>
                    </div>
                </div>' : '') . '
                
                <form method="POST" class="space-y-6">
                    ' . CSRFHelper::getTokenField() . '
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-gray-400"></i>
                            Nome Completo
                        </label>
                        <input type="text" 
                               name="nome" 
                               value="' . htmlspecialchars($_POST['nome'] ?? '') . '"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               placeholder="Seu nome completo">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                            Email
                        </label>
                        <input type="email" 
                               name="email" 
                               value="' . htmlspecialchars($_POST['email'] ?? '') . '"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               placeholder="seu@email.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>
                            Senha
                        </label>
                        <input type="password" 
                               name="password" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>
                            Confirmar Senha
                        </label>
                        <input type="password" 
                               name="confirm_password" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               placeholder="Digite a senha novamente">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>
                        Criar Conta
                    </button>
                </form>
                
                <div class="mt-6 text-center space-y-4">
                    <p class="text-gray-600">
                        Já tem uma conta? 
                        <a href="/login.php" class="text-green-600 hover:text-green-700 font-medium transition-colors">
                            Faça login aqui
                        </a>
                    </p>
                    
                    <a href="/" class="inline-flex items-center text-white hover:text-green-100 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar ao Início
                    </a>
                </div>
            </div>
        </div>
    </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Cadastro', $content, false, false);
?>