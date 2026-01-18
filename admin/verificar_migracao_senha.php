<?php
/**
 * Script para verificar e aplicar migração de password_change_required
 * Execute este arquivo uma vez após o deploy para garantir que a coluna existe
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

try {
    if ($db->isSQLite()) {
        // SQLite
        $dbPath = __DIR__ . '/../config/estudos.db';
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar se a coluna existe
        $query = $pdo->query("PRAGMA table_info(usuarios)");
        $columns = $query->fetchAll(PDO::FETCH_ASSOC);
        $hasColumn = false;

        foreach ($columns as $column) {
            if ($column['name'] === 'password_change_required') {
                $hasColumn = true;
                break;
            }
        }

        if (!$hasColumn) {
            // Adicionar coluna
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN password_change_required BOOLEAN DEFAULT 0");

            // Marcar admin padrão
            $pdo->exec("UPDATE usuarios SET password_change_required = 1 WHERE email = 'admin@teste.com'");

            $message = "Coluna 'password_change_required' adicionada com sucesso!";
        } else {
            $message = "Coluna 'password_change_required' já existe. Nenhuma ação necessária.";
        }

    } else {
        // PostgreSQL
        $conn = $db->getConnection();

        // Verificar se a coluna existe
        $query = $conn->query("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name='usuarios' AND column_name='password_change_required'
        ");

        $hasColumn = $query->rowCount() > 0;

        if (!$hasColumn) {
            // Adicionar coluna
            $conn->exec("ALTER TABLE usuarios ADD COLUMN password_change_required BOOLEAN DEFAULT FALSE");

            // Marcar admin padrão
            $conn->exec("UPDATE usuarios SET password_change_required = TRUE WHERE email = 'admin@teste.com'");

            $message = "Coluna 'password_change_required' adicionada com sucesso!";
        } else {
            $message = "Coluna 'password_change_required' já existe. Nenhuma ação necessária.";
        }
    }

} catch (Exception $e) {
    $error = "Erro: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Migração - Password Change</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-database mr-2 text-blue-600"></i>
                        Verificação de Migração
                    </h1>
                    <p class="text-gray-600">Migração: password_change_required</p>
                </div>

                <?php if ($message): ?>
                    <div class="p-6 bg-green-50 border border-green-200 rounded-lg mb-6">
                        <h2 class="font-semibold text-green-900 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>
                            Sucesso
                        </h2>
                        <div class="text-green-800">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="p-6 bg-red-50 border border-red-200 rounded-lg mb-6">
                        <h2 class="font-semibold text-red-900 mb-2">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Erro
                        </h2>
                        <div class="text-red-800">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Nota:</strong> Execute este script apenas uma vez após o deploy. Após a execução bem-sucedida, você pode deletar este arquivo.
                    </p>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <a href="/admin/" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Voltar para Admin
                    </a>
                    <a href="/" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-home mr-2"></i>
                        Ir para Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
