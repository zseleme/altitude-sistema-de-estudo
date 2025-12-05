<?php
/**
 * Migration: Corrige modelo Gemini no banco de dados
 *
 * Atualiza gemini-2.5-flash para gemini-1.5-flash
 */

require_once __DIR__ . '/../config/database.php';

echo "=== FIX: Corrigindo modelo Gemini ===\n\n";

try {
    $db = Database::getInstance();

    // Verificar modelo atual
    $modelAtual = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = 'gemini_model'");

    if ($modelAtual) {
        echo "📋 Modelo atual: " . $modelAtual['valor'] . "\n";

        if (strpos($modelAtual['valor'], '2.5') !== false) {
            // Atualizar para modelo correto
            $db->execute(
                "UPDATE configuracoes SET valor = 'gemini-1.5-flash' WHERE chave = 'gemini_model'",
                []
            );
            echo "✅ Modelo atualizado para: gemini-1.5-flash\n";
        } else {
            echo "✅ Modelo já está correto\n";
        }
    } else {
        echo "⚠️  Configuração não encontrada no banco\n";
    }

    echo "\n✅ Correção executada com sucesso!\n\n";
    echo "Agora vá em Administração → Configurações de IA e salve as configurações novamente.\n";

} catch (Exception $e) {
    echo "❌ Erro ao executar correção: " . $e->getMessage() . "\n";
    exit(1);
}
?>
