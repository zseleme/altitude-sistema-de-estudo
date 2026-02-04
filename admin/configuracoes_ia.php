<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/encryption_helper.php';
require_once __DIR__ . '/../includes/input_validator.php';
requireAdmin();

$db = Database::getInstance();
$success = '';
$error = '';

// Processar salvamento
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF token
        CSRFHelper::validateRequest(false);

        $provider = $_POST['ai_provider'] ?? 'gemini';

        // Validar dados de entrada
        $validation = InputValidator::validateAIConfigData($_POST);

        if (!$validation['valid']) {
            $error = 'Dados inválidos: ' . implode(', ', $validation['errors']);
        } else {
            $db->beginTransaction();

            // Atualizar todas as configurações
            $configs = [
                'ai_provider' => $provider,
                'openai_api_key' => trim($_POST['openai_api_key'] ?? ''),
                'openai_model' => trim($_POST['openai_model'] ?? 'gpt-4o-mini'),
                'gemini_api_key' => trim($_POST['gemini_api_key'] ?? ''),
                'gemini_model' => trim($_POST['gemini_model'] ?? 'gemini-2.5-flash'),
                'groq_api_key' => trim($_POST['groq_api_key'] ?? ''),
                'groq_model' => trim($_POST['groq_model'] ?? 'llama-3.1-8b-instant'),
                'youtube_api_key' => trim($_POST['youtube_api_key'] ?? ''),
                'ai_temperature' => trim($_POST['ai_temperature'] ?? '0.3'),
                'ai_max_tokens' => trim($_POST['ai_max_tokens'] ?? '4000')
            ];

        // Encrypt API keys before saving
        $keysToEncrypt = ['openai_api_key', 'gemini_api_key', 'groq_api_key', 'youtube_api_key'];

        foreach ($configs as $chave => $valor) {
            // Pular API keys vazias para não sobrescrever valores existentes
            if (in_array($chave, $keysToEncrypt) && empty($valor)) {
                continue;
            }

            // Encrypt API keys
            if (in_array($chave, $keysToEncrypt) && !empty($valor)) {
                $valor = EncryptionHelper::encryptIfNeeded($valor);
            }

            // Verificar se o registro existe
            $exists = $db->fetchOne("SELECT id, valor FROM configuracoes WHERE chave = ?", [$chave]);

            if ($exists) {
                // UPDATE se existe
                $db->execute(
                    "UPDATE configuracoes SET valor = ?, data_atualizacao = CURRENT_TIMESTAMP WHERE chave = ?",
                    [$valor, $chave]
                );
            } else {
                // INSERT se não existe
                $db->execute(
                    "INSERT INTO configuracoes (chave, valor, descricao, tipo, data_criacao, data_atualizacao) VALUES (?, ?, '', 'text', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$chave, $valor]
                );
            }
        }

            $db->commit();
            $success = 'Configurações salvas com sucesso!';
        }
    } catch (Exception $e) {
        try {
            $db->rollback();
        } catch (Exception $rollbackError) {
            // Ignora erro de rollback se não houver transação ativa
        }
        $error = 'Erro ao salvar configurações: ' . $e->getMessage();
    }
}

// Buscar configurações atuais
$configsRaw = $db->fetchAll("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'ai_%' OR chave LIKE '%_api_key' OR chave LIKE '%_model'");
$configs = [];
$keysToDecrypt = ['openai_api_key', 'gemini_api_key', 'groq_api_key', 'youtube_api_key'];

foreach ($configsRaw as $config) {
    $chave = $config['chave'];
    $valor = $config['valor'];

    // Decrypt API keys for display
    if (in_array($chave, $keysToDecrypt) && !empty($valor)) {
        try {
            $valorDecrypt = EncryptionHelper::decrypt($valor);
            // Se descriptografia falhar (retornar vazio/falso), manter o valor original
            if ($valorDecrypt !== false && $valorDecrypt !== '') {
                $valor = $valorDecrypt;
            }
        } catch (Exception $e) {
            // Manter valor original se descriptografia falhar
        }
    }

    $configs[$chave] = $valor;
}

// Verificar se tem YouTube API Key configurada
$youtubeConfigured = !empty($configs['youtube_api_key']);

// Verificar status da configuração
$provider = $configs['ai_provider'] ?? 'gemini';
$isConfigured = false;
$statusMessage = '';

switch ($provider) {
    case 'openai':
        $isConfigured = !empty($configs['openai_api_key']) && $configs['openai_api_key'] !== 'sua-chave-openai-aqui';
        $statusMessage = $isConfigured ? 'OpenAI configurado ✓' : 'Configure a chave da API OpenAI';
        break;
    case 'gemini':
        $isConfigured = !empty($configs['gemini_api_key']) && $configs['gemini_api_key'] !== 'sua-chave-gemini-aqui';
        $statusMessage = $isConfigured ? 'Google Gemini configurado ✓' : 'Configure a chave da API Gemini';
        break;
    case 'groq':
        $isConfigured = !empty($configs['groq_api_key']) && $configs['groq_api_key'] !== 'sua-chave-groq-aqui';
        $statusMessage = $isConfigured ? 'Groq configurado ✓' : 'Configure a chave da API Groq';
        break;
}

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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Configurações de IA</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">🤖 Configurações de IA</h1>
                    <p class="text-gray-600 mt-2">Configure a API de Inteligência Artificial para revisão de textos em inglês</p>
                </div>

                <!-- Status -->
                <div class="mb-6 p-4 ' . ($isConfigured ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200') . ' border rounded-lg">
                    <div class="flex items-center">
                        <i class="fas ' . ($isConfigured ? 'fa-check-circle text-green-600' : 'fa-exclamation-triangle text-yellow-600') . ' text-xl mr-3"></i>
                        <div>
                            <p class="font-medium ' . ($isConfigured ? 'text-green-900' : 'text-yellow-900') . '">' . htmlspecialchars($statusMessage) . '</p>
                            <p class="text-sm ' . ($isConfigured ? 'text-green-700' : 'text-yellow-700') . ' mt-1">
                                Provedor atual: <strong>' . strtoupper($provider) . '</strong>
                            </p>
                        </div>
                    </div>
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

                <!-- Info Card -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-blue-900 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        Sobre os Provedores de IA
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-star text-green-600"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900">Google Gemini</h4>
                            </div>
                            <p class="text-gray-700 mb-2"><strong>Recomendado</strong> - Gratuito</p>
                            <p class="text-gray-600 text-xs">60 requisições/minuto</p>
                            <a href="https://makersuite.google.com/app/apikey" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs mt-2 inline-block">
                                Obter chave <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>

                        <div class="bg-white p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-bolt text-purple-600"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900">Groq</h4>
                            </div>
                            <p class="text-gray-700 mb-2">Gratuito - Rápido</p>
                            <p class="text-gray-600 text-xs">Llama, Mixtral</p>
                            <a href="https://console.groq.com/keys" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs mt-2 inline-block">
                                Obter chave <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>

                        <div class="bg-white p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-robot text-blue-600"></i>
                                </div>
                                <h4 class="font-semibold text-gray-900">OpenAI</h4>
                            </div>
                            <p class="text-gray-700 mb-2">Pago - Premium</p>
                            <p class="text-gray-600 text-xs">~$0.15 / 1000 tokens</p>
                            <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs mt-2 inline-block">
                                Obter chave <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Configuration Form -->
                <form method="POST" class="space-y-6">
                    <?php echo CSRFHelper::getTokenField(); ?>
                    <!-- Provider Selection -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-cog mr-2 text-blue-600"></i>
                            Escolher Provedor de IA
                        </h2>

                        <div class="space-y-3">
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ' . ($provider === 'gemini' ? 'border-green-500 bg-green-50' : 'border-gray-200') . '">
                                <input type="radio" name="ai_provider" value="gemini" ' . ($provider === 'gemini' ? 'checked' : '') . ' class="w-4 h-4 text-green-600">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center">
                                        <span class="font-semibold text-gray-900">Google Gemini</span>
                                        <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded">Recomendado</span>
                                        <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">Gratuito</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">Excelente qualidade, totalmente gratuito com limite generoso</p>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ' . ($provider === 'groq' ? 'border-purple-500 bg-purple-50' : 'border-gray-200') . '">
                                <input type="radio" name="ai_provider" value="groq" ' . ($provider === 'groq' ? 'checked' : '') . ' class="w-4 h-4 text-purple-600">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center">
                                        <span class="font-semibold text-gray-900">Groq</span>
                                        <span class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-800 text-xs rounded">Rápido</span>
                                        <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">Gratuito</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">Muito rápido, gratuito, usa modelos Llama e Mixtral</p>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ' . ($provider === 'openai' ? 'border-blue-500 bg-blue-50' : 'border-gray-200') . '">
                                <input type="radio" name="ai_provider" value="openai" ' . ($provider === 'openai' ? 'checked' : '') . ' class="w-4 h-4 text-blue-600">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center">
                                        <span class="font-semibold text-gray-900">OpenAI (ChatGPT)</span>
                                        <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded">Pago</span>
                                        <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded">Premium</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">Qualidade superior, mas requer créditos pagos</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- YouTube API Key -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fab fa-youtube mr-2 text-red-600"></i>
                            YouTube Data API
                        </h2>
                        <p class="text-gray-600 mb-4 text-sm">
                            Necessário para importar playlists do YouTube automaticamente.
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 hover:text-blue-800">
                                Obter chave <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </p>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Chave da API do YouTube
                                ' . ($youtubeConfigured ? '<span class="text-green-600 ml-2"><i class="fas fa-check-circle"></i> Configurado</span>' : '') . '
                            </label>
                            <input type="password" name="youtube_api_key" value="' . htmlspecialchars($configs['youtube_api_key'] ?? '') . '"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="AIza...">
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Ative a "YouTube Data API v3" no Google Cloud Console e crie uma chave de API.
                            </p>
                        </div>
                    </div>

                    <!-- API Keys Configuration -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-key mr-2 text-blue-600"></i>
                            Chaves de API - Inteligência Artificial
                        </h2>

                        <div class="space-y-6">
                            <!-- Google Gemini -->
                            <div id="gemini-config" class="p-4 bg-gray-50 rounded-lg">
                                <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-star text-green-600 mr-2"></i>
                                    Google Gemini
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Chave da API</label>
                                        <input type="password" name="gemini_api_key" value="' . htmlspecialchars($configs['gemini_api_key'] ?? '') . '"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="AIza...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                                        <select name="gemini_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="gemini-2.5-flash" ' . (($configs['gemini_model'] ?? '') === 'gemini-2.5-flash' ? 'selected' : '') . '>gemini-2.5-flash (Recomendado)</option>
                                            <option value="gemini-2.5-flash-lite" ' . (($configs['gemini_model'] ?? '') === 'gemini-2.5-flash-lite' ? 'selected' : '') . '>gemini-2.5-flash-lite (Mais rápido)</option>
                                            <option value="gemini-2.5-pro" ' . (($configs['gemini_model'] ?? '') === 'gemini-2.5-pro' ? 'selected' : '') . '>gemini-2.5-pro (Mais preciso)</option>
                                            <option value="gemini-2.0-flash" ' . (($configs['gemini_model'] ?? '') === 'gemini-2.0-flash' ? 'selected' : '') . '>gemini-2.0-flash</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Groq -->
                            <div id="groq-config" class="p-4 bg-gray-50 rounded-lg">
                                <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-bolt text-purple-600 mr-2"></i>
                                    Groq
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Chave da API</label>
                                        <input type="password" name="groq_api_key" value="' . htmlspecialchars($configs['groq_api_key'] ?? '') . '"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="gsk_...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                                        <select name="groq_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="llama-3.1-8b-instant" ' . (($configs['groq_model'] ?? '') === 'llama-3.1-8b-instant' ? 'selected' : '') . '>llama-3.1-8b-instant (Rápido)</option>
                                            <option value="llama-3.1-70b-versatile" ' . (($configs['groq_model'] ?? '') === 'llama-3.1-70b-versatile' ? 'selected' : '') . '>llama-3.1-70b-versatile (Preciso)</option>
                                            <option value="mixtral-8x7b-32768" ' . (($configs['groq_model'] ?? '') === 'mixtral-8x7b-32768' ? 'selected' : '') . '>mixtral-8x7b-32768 (Balanceado)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- OpenAI -->
                            <div id="openai-config" class="p-4 bg-gray-50 rounded-lg">
                                <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-robot text-blue-600 mr-2"></i>
                                    OpenAI (ChatGPT)
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Chave da API</label>
                                        <input type="password" name="openai_api_key" value="' . htmlspecialchars($configs['openai_api_key'] ?? '') . '"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="sk-...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                                        <select name="openai_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="gpt-4o-mini" ' . (($configs['openai_model'] ?? '') === 'gpt-4o-mini' ? 'selected' : '') . '>gpt-4o-mini (Econômico)</option>
                                            <option value="gpt-4o" ' . (($configs['openai_model'] ?? '') === 'gpt-4o' ? 'selected' : '') . '>gpt-4o (Premium)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-sliders-h mr-2 text-blue-600"></i>
                            Configurações Avançadas
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Temperatura (Criatividade)
                                    <span class="text-gray-500 font-normal">- 0.0 = Consistente, 1.0 = Criativo</span>
                                </label>
                                <input type="number" name="ai_temperature" value="' . htmlspecialchars($configs['ai_temperature'] ?? '0.3') . '"
                                       min="0" max="1" step="0.1"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Máximo de Tokens
                                    <span class="text-gray-500 font-normal">- Tamanho da resposta</span>
                                </label>
                                <input type="number" name="ai_max_tokens" value="' . htmlspecialchars($configs['ai_max_tokens'] ?? '4000') . '"
                                       min="1000" max="8000" step="500"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Salvar Configurações
                        </button>
                    </div>
                </form>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Configurações de IA - Administração', $content, true, true);
?>
