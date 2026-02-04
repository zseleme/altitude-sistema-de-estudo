<?php
/**
 * One-time migration script to encrypt existing API keys in database
 * Run this once after deploying the encryption feature
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/encryption_helper.php';
requireAdmin();

$db = Database::getInstance();

// Keys that should be encrypted
$keysToEncrypt = ['openai_api_key', 'gemini_api_key', 'groq_api_key', 'youtube_api_key'];

$migrated = 0;
$skipped = 0;
$errors = [];

try {
    $db->beginTransaction();

    foreach ($keysToEncrypt as $keyName) {
        // Fetch current value
        $result = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = ?", [$keyName]);

        if (!$result || empty($result['valor'])) {
            $skipped++;
            continue;
        }

        $currentValue = $result['valor'];

        // Skip placeholder values
        $placeholders = ['sua-chave-openai-aqui', 'sua-chave-gemini-aqui', 'sua-chave-groq-aqui'];
        if (in_array($currentValue, $placeholders)) {
            $skipped++;
            continue;
        }

        // Check if already encrypted
        if (EncryptionHelper::isEncrypted($currentValue)) {
            $skipped++;
            continue;
        }

        // Encrypt the value
        $encryptedValue = EncryptionHelper::encrypt($currentValue);

        // Update in database
        $db->execute(
            "UPDATE configuracoes SET valor = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE chave = ?",
            [$encryptedValue, $keyName]
        );

        $migrated++;
    }

    $db->commit();

    $message = "Migration completed successfully!<br>";
    $message .= "Keys encrypted: {$migrated}<br>";
    $message .= "Keys skipped (empty or already encrypted): {$skipped}";

    if (!empty($errors)) {
        $message .= "<br><br>Errors:<br>" . implode("<br>", $errors);
    }

} catch (Exception $e) {
    $db->rollback();
    $message = "Migration failed: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encrypt API Keys Migration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-600"></i>
                        API Keys Encryption Migration
                    </h1>
                    <p class="text-gray-600">One-time script to encrypt existing API keys in the database</p>
                </div>

                <div class="p-6 bg-blue-50 border border-blue-200 rounded-lg mb-6">
                    <h2 class="font-semibold text-blue-900 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>
                        Migration Result
                    </h2>
                    <div class="text-blue-800">
                        <?php echo $message; ?>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <a href="/admin/configuracoes_ia.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to AI Configuration
                    </a>
                    <a href="/home.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-home mr-2"></i>
                        Go to Dashboard
                    </a>
                </div>

                <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Security Note:</strong> After running this migration successfully, you can delete this file (admin/migrate_encrypt_keys.php) for security.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
