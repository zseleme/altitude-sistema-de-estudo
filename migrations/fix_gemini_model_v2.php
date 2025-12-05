<?php
/**
 * Migration: Atualiza modelo Gemini para usar versão latest
 */

require_once __DIR__ . '/../config/database.php';

echo "=== FIX: Atualizando modelo Gemini para versão compatível ===\n\n";

try {
    $db = Database::getInstance();

    // Verificar modelo atual
    $modelAtual = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = 'gemini_model'");

    if ($modelAtual) {
        echo "📋 Modelo atual: " . $modelAtual['valor'] . "\n";

        // Atualizar para modelo correto
        $db->execute(
            "UPDATE configuracoes SET valor = 'gemini-2.5-flash' WHERE chave = 'gemini_model'",
            []
        );
        echo "✅ Modelo atualizado para: gemini-2.5-flash\n";
    } else {
        echo "⚠️  Configuração não encontrada no banco\n";
        echo "Criando configuração...\n";
        $db->execute(
            "INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES (?, ?, ?, ?)",
            ['gemini_model', 'gemini-2.5-flash', 'Modelo Gemini', 'text']
        );
        echo "✅ Configuração criada com sucesso!\n";
    }

    echo "\n✅ Correção executada com sucesso!\n\n";
    echo "O modelo Gemini foi atualizado para gemini-2.5-flash (versão estável e recomendada).\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar correção: " . $e->getMessage() . "\n";
    exit(1);
}
?>
