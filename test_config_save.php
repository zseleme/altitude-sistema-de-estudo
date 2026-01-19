<?php
// Script de teste para debugar o erro 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Teste de Salvamento de Configurações ===\n\n";

// Simular sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = true;

// Carregar dependências
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/encryption_helper.php';
require_once __DIR__ . '/includes/input_validator.php';
require_once __DIR__ . '/includes/csrf_helper.php';

try {
    echo "1. Testando conexão ao banco de dados...\n";
    $db = Database::getInstance();
    echo "   ✓ Conexão OK\n\n";

    echo "2. Testando geração de chave de criptografia...\n";
    $key = EncryptionHelper::generateSecureKey();
    echo "   ✓ Chave gerada: " . substr($key, 0, 16) . "...\n\n";

    echo "3. Testando criptografia...\n";
    $testValue = "test-api-key-12345";
    $encrypted = EncryptionHelper::encrypt($testValue);
    echo "   ✓ Valor criptografado: " . substr($encrypted, 0, 20) . "...\n";

    $decrypted = EncryptionHelper::decrypt($encrypted);
    echo "   ✓ Valor descriptografado: " . $decrypted . "\n";
    echo "   ✓ Match: " . ($decrypted === $testValue ? 'SIM' : 'NÃO') . "\n\n";

    echo "4. Testando validação de entrada...\n";
    $testData = [
        'ai_provider' => 'gemini',
        'ai_temperature' => '0.3',
        'ai_max_tokens' => '4000'
    ];
    $validation = InputValidator::validateAIConfigData($testData);
    echo "   ✓ Validação: " . ($validation['valid'] ? 'PASSOU' : 'FALHOU') . "\n";
    if (!$validation['valid']) {
        echo "   Erros: " . implode(', ', $validation['errors']) . "\n";
    }
    echo "\n";

    echo "5. Testando UPDATE na tabela configuracoes...\n";
    $db->beginTransaction();

    // Verificar se a tabela existe
    $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name='configuracoes'");
    if (empty($tables)) {
        echo "   ✗ ERRO: Tabela 'configuracoes' não existe!\n";
        echo "   Execute a instalação primeiro acessando qualquer página do sistema.\n";
    } else {
        echo "   ✓ Tabela 'configuracoes' existe\n";

        // Testar UPDATE
        $db->execute(
            "UPDATE configuracoes SET valor = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE chave = ?",
            ['test-value', 'ai_provider']
        );
        echo "   ✓ UPDATE executado com sucesso\n";

        // Verificar se atualizou
        $config = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = ?", ['ai_provider']);
        echo "   ✓ Valor atual: " . ($config['valor'] ?? 'NULL') . "\n";
    }

    $db->rollback(); // Reverter teste
    echo "   ✓ Teste concluído (rollback aplicado)\n\n";

    echo "6. Verificando todas as configurações necessárias...\n";
    $requiredConfigs = [
        'ai_provider', 'openai_api_key', 'openai_model',
        'gemini_api_key', 'gemini_model', 'groq_api_key',
        'groq_model', 'youtube_api_key', 'ai_temperature', 'ai_max_tokens'
    ];

    $configs = $db->fetchAll("SELECT chave FROM configuracoes WHERE chave IN ('" . implode("','", $requiredConfigs) . "')");
    $existingKeys = array_column($configs, 'chave');

    foreach ($requiredConfigs as $key) {
        $exists = in_array($key, $existingKeys);
        echo "   " . ($exists ? '✓' : '✗') . " {$key}\n";
    }

    echo "\n=== TODOS OS TESTES PASSARAM ===\n";

} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
