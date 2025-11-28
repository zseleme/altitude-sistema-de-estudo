<?php
/**
 * Migration: Adiciona tabela de configurações de IA
 *
 * Permite configurar as APIs de IA através da interface administrativa
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MIGRATION: Adicionando tabela de configurações de IA ===\n\n";

try {
    $db = Database::getInstance();

    if ($db->isSQLite()) {
        echo "📊 Banco de dados: SQLite\n\n";

        // Criar tabela de configurações
        $db->execute("
            CREATE TABLE IF NOT EXISTS configuracoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                chave VARCHAR(100) UNIQUE NOT NULL,
                valor TEXT,
                descricao TEXT,
                tipo VARCHAR(50) DEFAULT 'text',
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Tabela 'configuracoes' criada (SQLite)\n";

    } else {
        echo "📊 Banco de dados: PostgreSQL\n\n";

        // Criar tabela de configurações
        $db->execute("
            CREATE TABLE IF NOT EXISTS configuracoes (
                id SERIAL PRIMARY KEY,
                chave VARCHAR(100) UNIQUE NOT NULL,
                valor TEXT,
                descricao TEXT,
                tipo VARCHAR(50) DEFAULT 'text',
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Tabela 'configuracoes' criada (PostgreSQL)\n";
    }

    // Inserir configurações padrão de IA
    echo "\n📝 Inserindo configurações padrão...\n";

    $configsPadrao = [
        ['ai_provider', 'gemini', 'Provedor de IA (openai, gemini, groq)', 'select'],
        ['openai_api_key', '', 'Chave da API OpenAI', 'password'],
        ['openai_model', 'gpt-4o-mini', 'Modelo OpenAI', 'text'],
        ['gemini_api_key', '', 'Chave da API Google Gemini', 'password'],
        ['gemini_model', 'gemini-2.5-flash', 'Modelo Gemini', 'text'],
        ['groq_api_key', '', 'Chave da API Groq', 'password'],
        ['groq_model', 'llama-3.1-8b-instant', 'Modelo Groq', 'text'],
        ['ai_temperature', '0.3', 'Temperatura (0.0-1.0)', 'number'],
        ['ai_max_tokens', '2000', 'Máximo de tokens', 'number']
    ];

    foreach ($configsPadrao as $config) {
        try {
            $db->execute(
                "INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES (?, ?, ?, ?)",
                $config
            );
            echo "  ✅ " . $config[0] . "\n";
        } catch (Exception $e) {
            // Já existe, ignorar
            echo "  ⚠️  " . $config[0] . " (já existe)\n";
        }
    }

    echo "\n✅ Migration executada com sucesso!\n";
    echo "\nTabela criada:\n";
    echo "  • configuracoes - Armazena configurações do sistema\n\n";

    echo "Configurações de IA disponíveis:\n";
    echo "  • ai_provider: Provedor escolhido (openai/gemini/groq)\n";
    echo "  • *_api_key: Chaves de API (criptografadas)\n";
    echo "  • *_model: Modelos a serem usados\n";
    echo "  • ai_temperature: Controle de criatividade\n";
    echo "  • ai_max_tokens: Limite de resposta\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
