<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf_helper.php';

$error = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        CSRFHelper::validateRequest(false);

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email e senha são obrigatórios';
        } else {
            if (login($email, $password)) {
                header('Location: /home.php');
                exit;
            } else {
                $error = 'Email ou senha incorretos';
            }
        }
    } catch (Exception $e) {
        $error = 'Requisição inválida. Tente novamente.';
        error_log("CSRF validation failed on login: " . $e->getMessage());
    }
}

$content = '
    <div class="min-h-screen bg-gradient-to-br from-blue-600 to-purple-700 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-white rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-mountain text-3xl text-blue-600"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">Altitude</h2>
                <p class="text-blue-100">Faça login para acessar seus cursos</p>
            </div>
                                    
            <div class="bg-white rounded-xl shadow-2xl p-8">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 text-center mb-2">
                        <i class="fas fa-sign-in-alt mr-2 text-blue-600"></i>
                        Login
                    </h3>
                                    </div>
                                    
                ' . ($error ? '<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                                        </div>
                </div>' : '') . '
                
                <form method="POST" class="space-y-6">
                    ' . CSRFHelper::getTokenField() . '
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                            Email
                        </label>
                        <input type="email" 
                               name="email" 
                               value="' . htmlspecialchars($_POST['email'] ?? '') . '"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
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
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Sua senha">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Entrar
                    </button>
                </form>
                
                <div class="mt-6 text-center space-y-4">
                    <p class="text-gray-600">
                        Não tem uma conta? 
                        <a href="/register.php" class="text-blue-600 hover:text-blue-700 font-medium transition-colors">
                            Cadastre-se aqui
                        </a>
                    </p>
                    
                    <a href="/" class="inline-flex items-center text-white hover:text-blue-100 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar ao Início
                    </a>
                </div>
            </div>
        </div>
    </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Login', $content, false, false);
?>