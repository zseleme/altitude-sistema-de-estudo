<?php
/**
 * Migration: Aumenta limite de tokens para análise de questões
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MIGRATION: Aumentando limite de tokens ===\n\n";

try {
    $db = Database::getInstance();

    // Verificar valor atual
    $tokenAtual = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = 'ai_max_tokens'");

    if ($tokenAtual) {
        echo "📋 Limite atual: " . $tokenAtual['valor'] . " tokens\n";

        $valorAtual = intval($tokenAtual['valor']);
        if ($valorAtual < 4000) {
            // Atualizar para 4000 tokens
            $db->execute(
                "UPDATE configuracoes SET valor = '4000' WHERE chave = 'ai_max_tokens'",
                []
            );
            echo "✅ Limite atualizado para: 4000 tokens\n";
        } else {
            echo "✅ Limite já está adequado\n";
        }
    } else {
        echo "⚠️  Configuração não encontrada no banco\n";
    }

    echo "\n✅ Migration executada com sucesso!\n\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
