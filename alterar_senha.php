<?php
// Disable error display to prevent header issues
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';

// Must be logged in to change password
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$isRequired = isset($_GET['required']) && $_GET['required'] == '1';
$success = '';
$error = '';

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF
        CSRFHelper::validateRequest(false);

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Todos os campos são obrigatórios.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'As senhas não coincidem.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'A nova senha deve ter no mínimo 8 caracteres.';
        } elseif ($newPassword === 'admin123') {
            $error = 'Por segurança, você não pode usar a senha padrão "admin123".';
        } else {
            // Verify current password
            $user = $db->fetchOne(
                "SELECT * FROM usuarios WHERE id = ?",
                [$userId]
            );

            if (!$user || !password_verify($currentPassword, $user['senha'])) {
                $error = 'Senha atual incorreta.';
            } else {
                // Check if new password is different from current
                if (password_verify($newPassword, $user['senha'])) {
                    $error = 'A nova senha deve ser diferente da senha atual.';
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    $db->execute(
                        "UPDATE usuarios SET senha = ?, password_change_required = ? WHERE id = ?",
                        [$hashedPassword, 0, $userId]
                    );

                    // Update session
                    $_SESSION['password_change_required'] = false;

                    $success = 'Senha alterada com sucesso!';

                    // If it was a required change, redirect to home after 2 seconds
                    if ($isRequired) {
                        header("refresh:2;url=/home.php");
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error changing password: " . $e->getMessage());
        $error = 'Erro ao alterar senha. Tente novamente.';
    }
}

// Get CSRF token
$csrfToken = CSRFHelper::getToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - Altitude</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div>
                <div class="flex justify-center">
                    <i class="fas fa-mountain text-5xl text-blue-600"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <?php echo $isRequired ? 'Troca de Senha Obrigatória' : 'Alterar Senha'; ?>
                </h2>
                <?php if ($isRequired): ?>
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                            <div>
                                <p class="text-sm text-yellow-800">
                                    <strong>Atenção!</strong> Por segurança, você precisa alterar sua senha antes de continuar.
                                </p>
                                <p class="text-xs text-yellow-700 mt-1">
                                    Não use senhas padrão como "admin123". Escolha uma senha forte e única.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Altere sua senha regularmente para manter sua conta segura
                    </p>
                <?php endif; ?>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <p class="text-sm text-green-800"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                    <?php if ($isRequired): ?>
                        <p class="text-xs text-green-700 mt-2">Redirecionando para o dashboard...</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                        <p class="text-sm text-red-800"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Password Change Form -->
            <form class="mt-8 space-y-6" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="rounded-md shadow-sm space-y-4">
                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                            Senha Atual
                        </label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Digite sua senha atual"
                        >
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                            Nova Senha
                        </label>
                        <input
                            id="new_password"
                            name="new_password"
                            type="password"
                            required
                            minlength="8"
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Digite uma nova senha (mínimo 8 caracteres)"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Mínimo 8 caracteres. Use letras, números e símbolos para maior segurança.
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar Nova Senha
                        </label>
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            required
                            minlength="8"
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Digite a nova senha novamente"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <?php if (!$isRequired): ?>
                        <a href="/home.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Voltar
                        </a>
                    <?php else: ?>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-lock mr-1"></i>
                            Troca obrigatória
                        </div>
                    <?php endif; ?>

                    <button
                        type="submit"
                        class="group relative flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-key mr-2"></i>
                        Alterar Senha
                    </button>
                </div>
            </form>

            <!-- Security Tips -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Dicas de Segurança
                </h3>
                <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                    <li>Use uma senha única que você não usa em outros sites</li>
                    <li>Combine letras maiúsculas, minúsculas, números e símbolos</li>
                    <li>Evite informações pessoais óbvias (nome, data de nascimento, etc.)</li>
                    <li>Considere usar um gerenciador de senhas</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
