#!/bin/bash
# Script rápido para sincronizar SQLite -> PostgreSQL

echo "======================================"
echo "  SQLite → PostgreSQL"
echo "======================================"
echo ""

# Verificar se deve fazer truncate
if [ "$1" == "--truncate" ]; then
    echo "⚠️  Modo: TRUNCATE (limpando dados existentes)"
    php sync_database.php --from=sqlite --to=postgresql --truncate
else
    echo "ℹ️  Modo: MERGE (preservando dados existentes)"
    echo "   Use '$0 --truncate' para limpar antes"
    php sync_database.php --from=sqlite --to=postgresql
fi



