<?php
/**
 * Helper para integração com APIs de IA
 * Suporta: OpenAI, Google Gemini, Groq
 */

// Verificar se a configuração existe
$aiConfigPath = __DIR__ . '/../config/openai.php';
if (file_exists($aiConfigPath)) {
    require_once $aiConfigPath;
}

class AIHelper {
    private $provider;
    private $apiKey;
    private $model;
    private $temperature;
    private $maxTokens;

    public function __construct() {
        $this->provider = defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini';
        $this->temperature = defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.3;
        $this->maxTokens = defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2000;

        // Configurar baseado no provedor
        switch ($this->provider) {
            case 'openai':
                if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'sua-chave-openai-aqui') {
                    throw new Exception('Chave da API OpenAI não configurada');
                }
                $this->apiKey = OPENAI_API_KEY;
                $this->model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini';
                break;

            case 'gemini':
                if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'sua-chave-gemini-aqui') {
                    throw new Exception('Chave da API Gemini não configurada');
                }
                $this->apiKey = GEMINI_API_KEY;
                $this->model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash';
                break;

            case 'groq':
                if (!defined('GROQ_API_KEY') || GROQ_API_KEY === 'sua-chave-groq-aqui') {
                    throw new Exception('Chave da API Groq não configurada');
                }
                $this->apiKey = GROQ_API_KEY;
                $this->model = defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant';
                break;

            default:
                throw new Exception('Provedor de IA inválido: ' . $this->provider);
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
        if (!defined('AI_PROVIDER')) {
            return false;
        }

        $provider = AI_PROVIDER;

        switch ($provider) {
            case 'openai':
                return defined('OPENAI_API_KEY') && OPENAI_API_KEY !== 'sua-chave-openai-aqui';
            case 'gemini':
                return defined('GEMINI_API_KEY') && GEMINI_API_KEY !== 'sua-chave-gemini-aqui';
            case 'groq':
                return defined('GROQ_API_KEY') && GROQ_API_KEY !== 'sua-chave-groq-aqui';
            default:
                return false;
        }
    }

    /**
     * Retorna o provedor configurado
     */
    public static function getProvider() {
        return defined('AI_PROVIDER') ? AI_PROVIDER : 'não configurado';
    }
}
?>
