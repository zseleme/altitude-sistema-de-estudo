<?php
/**
 * Configuração da API de IA para revisão de textos
 *
 * OPÇÕES DISPONÍVEIS:
 *
 * 1. OpenAI (ChatGPT) - PAGO
 *    - Muito preciso e natural
 *    - Custo: ~$0.15 por 1000 tokens (gpt-4o-mini)
 *    - Chave: https://platform.openai.com/api-keys
 *
 * 2. Google Gemini - GRATUITO (com limites)
 *    - Muito bom e gratuito
 *    - Limite: 60 requisições/minuto
 *    - Chave: https://makersuite.google.com/app/apikey
 *
 * 3. Groq - GRATUITO (com limites)
 *    - Rápido e gratuito
 *    - Usa modelos Llama, Mixtral
 *    - Chave: https://console.groq.com/keys
 */

// ===================================
// ESCOLHA O PROVEDOR
// ===================================
// Opções: 'openai', 'gemini', 'groq'
define('AI_PROVIDER', 'gemini');

// ===================================
// CONFIGURAÇÃO OPENAI
// ===================================
define('OPENAI_API_KEY', 'sua-chave-openai-aqui');
define('OPENAI_MODEL', 'gpt-4o-mini');

// ===================================
// CONFIGURAÇÃO GOOGLE GEMINI (RECOMENDADO - GRATUITO)
// ===================================
define('GEMINI_API_KEY', 'sua-chave-gemini-aqui');
define('GEMINI_MODEL', 'gemini-1.5-flash'); // ou gemini-1.5-pro

// ===================================
// CONFIGURAÇÃO GROQ (ALTERNATIVA GRATUITA)
// ===================================
define('GROQ_API_KEY', 'sua-chave-groq-aqui');
define('GROQ_MODEL', 'llama-3.1-8b-instant'); // ou mixtral-8x7b-32768

// ===================================
// CONFIGURAÇÕES GERAIS
// ===================================
define('AI_TEMPERATURE', 0.3);
define('AI_MAX_TOKENS', 2000);
?>
