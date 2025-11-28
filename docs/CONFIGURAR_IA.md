# 🤖 Configurar Revisão por IA

Este guia explica como configurar a revisão automática de textos em inglês usando APIs de IA.

## 📋 Opções Disponíveis

### 1. ✅ Google Gemini (RECOMENDADO - GRATUITO)

**Por que escolher:**
- ✅ Completamente gratuito
- ✅ 60 requisições por minuto
- ✅ Qualidade excelente
- ✅ Fácil de configurar

**Como obter chave:**
1. Acesse: https://makersuite.google.com/app/apikey
2. Faça login com sua conta Google
3. Clique em "Create API Key"
4. Copie a chave gerada

**Configuração:**
```php
define('AI_PROVIDER', 'gemini');
define('GEMINI_API_KEY', 'sua-chave-aqui');
define('GEMINI_MODEL', 'gemini-1.5-flash'); // Rápido e gratuito
```

---

### 2. ⚡ Groq (ALTERNATIVA GRATUITA)

**Por que escolher:**
- ✅ Gratuito
- ✅ Muito rápido
- ✅ Vários modelos disponíveis

**Como obter chave:**
1. Acesse: https://console.groq.com
2. Crie uma conta gratuita
3. Vá em "API Keys"
4. Crie uma nova chave

**Configuração:**
```php
define('AI_PROVIDER', 'groq');
define('GROQ_API_KEY', 'sua-chave-aqui');
define('GROQ_MODEL', 'llama-3.1-8b-instant'); // Rápido
```

**Modelos disponíveis:**
- `llama-3.1-8b-instant` - Mais rápido
- `llama-3.1-70b-versatile` - Mais preciso
- `mixtral-8x7b-32768` - Boa qualidade

---

### 3. 💰 OpenAI (ChatGPT) - PAGO

**Por que escolher:**
- ✅ Qualidade superior
- ❌ Pago (~$0.15 por 1000 tokens)

**Como obter chave:**
1. Acesse: https://platform.openai.com/api-keys
2. Crie uma conta
3. Adicione créditos ($5 mínimo)
4. Crie uma API key

**Configuração:**
```php
define('AI_PROVIDER', 'openai');
define('OPENAI_API_KEY', 'sk-...');
define('OPENAI_MODEL', 'gpt-4o-mini'); // Mais barato
```

---

## 🚀 Passo a Passo de Configuração

### 1. Copiar arquivo de configuração

```bash
cp config/openai.example.php config/openai.php
```

### 2. Editar configuração

Abra `config/openai.php` e configure:

```php
// Escolha o provedor (recomendado: gemini)
define('AI_PROVIDER', 'gemini');

// Cole sua chave da API
define('GEMINI_API_KEY', 'AIza...');

// Modelo (deixe o padrão)
define('GEMINI_MODEL', 'gemini-1.5-flash');
```

### 3. Salvar e testar

1. Salve o arquivo
2. Vá em "Inglês → Diário"
3. Escreva um texto em inglês
4. Clique em "Revisar com IA"

---

## ⚙️ Configurações Avançadas

### Temperatura (criatividade)
```php
define('AI_TEMPERATURE', 0.3); // 0.0 = consistente, 1.0 = criativo
```

### Máximo de tokens (tamanho da resposta)
```php
define('AI_MAX_TOKENS', 2000); // Padrão: 2000
```

---

## 🆘 Problemas Comuns

### "API não está configurada"
- Verifique se copiou o arquivo para `config/openai.php`
- Certifique-se de ter colado a chave correta
- Verifique se o provedor está correto

### "Erro da API: API key not valid"
- Sua chave está incorreta ou expirada
- Gere uma nova chave no site do provedor

### "Erro da API: Quota exceeded"
- Você excedeu o limite gratuito
- Para Gemini: aguarde 1 minuto
- Para Groq: aguarde alguns segundos

### Resposta muito lenta
- Para Gemini: normal (2-5 segundos)
- Para Groq: deve ser rápido (1-2 segundos)
- Para OpenAI: normal (2-4 segundos)

---

## 🎯 Recomendação

**Para uso pessoal/estudos:** Use **Google Gemini**
- Gratuito
- Ótima qualidade
- Limite generoso

**Para projetos profissionais:** Use **OpenAI**
- Melhor qualidade
- Mais confiável
- Suporte comercial

---

## 📊 Comparação

| Provedor | Custo | Qualidade | Velocidade | Limite |
|----------|-------|-----------|------------|--------|
| Gemini | Grátis | ⭐⭐⭐⭐⭐ | Rápido | 60/min |
| Groq | Grátis | ⭐⭐⭐⭐ | Muito rápido | Generoso |
| OpenAI | Pago | ⭐⭐⭐⭐⭐ | Rápido | Depende $ |

---

## 📝 Exemplo de Uso

1. Escreva seu diário em inglês
2. Clique em "Revisar com IA"
3. Aguarde alguns segundos
4. Veja a revisão completa com:
   - ✅ Versão corrigida
   - 📝 Explicação dos erros
   - 👍 Pontos positivos
   - 💡 Dicas de melhoria

---

## 🔒 Segurança

- Suas chaves de API ficam no servidor
- Nunca compartilhe suas chaves
- Use variáveis de ambiente em produção
- Adicione `config/openai.php` no `.gitignore`

---

Precisa de ajuda? Abra uma issue no GitHub!
