<?php
/**
 * Helper para integração com APIs de IA
 * Suporta: OpenAI, Google Gemini, Groq
 * Configurações lidas do banco de dados
 */

class AIHelper {
    private $provider;
    private $apiKey;
    private $model;
    private $temperature;
    private $maxTokens;

    public function __construct() {
        // Carregar configurações do banco de dados
        $configs = $this->loadConfigsFromDatabase();

        $this->provider = $configs['ai_provider'] ?? 'gemini';
        $this->temperature = floatval($configs['ai_temperature'] ?? 0.3);
        $this->maxTokens = intval($configs['ai_max_tokens'] ?? 2000);

        // Configurar baseado no provedor
        switch ($this->provider) {
            case 'openai':
                $this->apiKey = $configs['openai_api_key'] ?? '';
                $this->model = $configs['openai_model'] ?? 'gpt-4o-mini';
                if (empty($this->apiKey)) {
                    throw new Exception('Chave da API OpenAI não configurada');
                }
                break;

            case 'gemini':
                $this->apiKey = $configs['gemini_api_key'] ?? '';
                $this->model = $configs['gemini_model'] ?? 'gemini-1.5-flash';
                if (empty($this->apiKey)) {
                    throw new Exception('Chave da API Gemini não configurada');
                }
                break;

            case 'groq':
                $this->apiKey = $configs['groq_api_key'] ?? '';
                $this->model = $configs['groq_model'] ?? 'llama-3.1-8b-instant';
                if (empty($this->apiKey)) {
                    throw new Exception('Chave da API Groq não configurada');
                }
                break;

            default:
                throw new Exception('Provedor de IA inválido: ' . $this->provider);
        }
    }

    /**
     * Carrega configurações do banco de dados
     */
    private function loadConfigsFromDatabase() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();

            $configsRaw = $db->fetchAll("
                SELECT chave, valor
                FROM configuracoes
                WHERE chave LIKE 'ai_%' OR chave LIKE '%_api_key' OR chave LIKE '%_model'
            ");

            $configs = [];
            foreach ($configsRaw as $config) {
                $configs[$config['chave']] = $config['valor'];
            }

            return $configs;
        } catch (Exception $e) {
            // Se falhar, tentar arquivo de configuração legado
            $aiConfigPath = __DIR__ . '/../config/openai.php';
            if (file_exists($aiConfigPath)) {
                require_once $aiConfigPath;
                return [
                    'ai_provider' => defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini',
                    'openai_api_key' => defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '',
                    'openai_model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini',
                    'gemini_api_key' => defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '',
                    'gemini_model' => defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash',
                    'groq_api_key' => defined('GROQ_API_KEY') ? GROQ_API_KEY : '',
                    'groq_model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
                    'ai_temperature' => defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.3,
                    'ai_max_tokens' => defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2000
                ];
            }

            return [];
        }
    }

    /**
     * Revisa um texto em inglês como um professor expert
     */
    public function reviewEnglishText($text) {
        $systemPrompt = "You are an expert English teacher with years of experience teaching English as a second language. Your task is to review and correct student writing with care and encouragement.

When reviewing text, provide:
1. **Corrected Version**: A fully corrected version of the text with all grammar, spelling, vocabulary, and style improvements.
2. **Detailed Feedback**: Explain the main errors found (grammar, vocabulary, structure, etc.) in a clear and educational way.
3. **Positive Points**: Highlight what the student did well.
4. **Suggestions for Improvement**: Give 2-3 specific tips to help the student improve their writing.

Format your response in Portuguese (BR) to ensure the student understands the feedback clearly.

Be encouraging and constructive - remember, mistakes are part of learning!";

        $userPrompt = "Please review the following English text written by a student:\n\n---\n{$text}\n---\n\nProvide your review following the format described.";

        switch ($this->provider) {
            case 'openai':
                return $this->sendOpenAIRequest($systemPrompt, $userPrompt);
            case 'gemini':
                return $this->sendGeminiRequest($systemPrompt, $userPrompt);
            case 'groq':
                return $this->sendGroqRequest($systemPrompt, $userPrompt);
        }
    }

    /**
     * OpenAI API
     */
    private function sendOpenAIRequest($systemPrompt, $userPrompt) {
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $response = $this->curlRequest($url, $data, $headers);

        return [
            'review' => $response['choices'][0]['message']['content'],
            'tokens_used' => $response['usage']['total_tokens'] ?? 0
        ];
    }

    /**
     * Google Gemini API (GRATUITO)
     */
    private function sendGeminiRequest($systemPrompt, $userPrompt) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $combinedPrompt = $systemPrompt . "\n\n" . $userPrompt;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $combinedPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens
            ]
        ];

        $headers = ['Content-Type: application/json'];

        $response = $this->curlRequest($url, $data, $headers);

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Resposta inválida da API Gemini');
        }

        return [
            'review' => $response['candidates'][0]['content']['parts'][0]['text'],
            'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0
        ];
    }

    /**
     * Groq API (GRATUITO)
     */
    private function sendGroqRequest($systemPrompt, $userPrompt) {
        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $response = $this->curlRequest($url, $data, $headers);

        return [
            'review' => $response['choices'][0]['message']['content'],
            'tokens_used' => $response['usage']['total_tokens'] ?? 0
        ];
    }

    /**
     * Helper para requisições CURL
     */
    private function curlRequest($url, $data, $headers) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Erro na requisição: ' . $error);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? $errorData['error'] ?? 'Erro desconhecido';
            throw new Exception('Erro da API: ' . $errorMessage);
        }

        $result = json_decode($response, true);

        if (!$result) {
            throw new Exception('Resposta inválida da API');
        }

        return $result;
    }

    /**
     * Verifica se a API está configurada
     */
    public static function isConfigured() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();

            $configsRaw = $db->fetchAll("
                SELECT chave, valor
                FROM configuracoes
                WHERE chave IN ('ai_provider', 'openai_api_key', 'gemini_api_key', 'groq_api_key')
            ");

            $configs = [];
            foreach ($configsRaw as $config) {
                $configs[$config['chave']] = $config['valor'];
            }

            $provider = $configs['ai_provider'] ?? '';

            switch ($provider) {
                case 'openai':
                    return !empty($configs['openai_api_key']);
                case 'gemini':
                    return !empty($configs['gemini_api_key']);
                case 'groq':
                    return !empty($configs['groq_api_key']);
                default:
                    return false;
            }
        } catch (Exception $e) {
            // Fallback para arquivo de configuração
            if (!defined('AI_PROVIDER')) {
                return false;
            }

            $provider = AI_PROVIDER;

            switch ($provider) {
                case 'openai':
                    return defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY);
                case 'gemini':
                    return defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY);
                case 'groq':
                    return defined('GROQ_API_KEY') && !empty(GROQ_API_KEY);
                default:
                    return false;
            }
        }
    }

    /**
     * Retorna o provedor configurado
     */
    public static function getProvider() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();

            $provider = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = 'ai_provider'");
            return $provider ? $provider['valor'] : 'não configurado';
        } catch (Exception $e) {
            return defined('AI_PROVIDER') ? AI_PROVIDER : 'não configurado';
        }
    }
}
?>
