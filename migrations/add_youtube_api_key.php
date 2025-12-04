<?php
/**
 * Migration: Adiciona configuração para YouTube Data API
 *
 * Permite importar playlists do YouTube automaticamente
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MIGRATION: Adicionando configuração YouTube API ===\n\n";

try {
    $db = Database::getInstance();

    echo "📝 Inserindo configuração para YouTube API...\n";

    try {
        $db->execute(
            "INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES (?, ?, ?, ?)",
            ['youtube_api_key', '', 'Chave da API YouTube Data v3', 'password']
        );
        echo "  ✅ youtube_api_key adicionada\n";
    } catch (Exception $e) {
        // Já existe, ignorar
        echo "  ⚠️  youtube_api_key (já existe)\n";
    }

    echo "\n✅ Migration executada com sucesso!\n";
    echo "\nConfiguração adicionada:\n";
    echo "  • youtube_api_key: Chave para YouTube Data API v3\n";
    echo "  • Permite importar playlists inteiras automaticamente\n\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
