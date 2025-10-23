#!/bin/bash
# Script rápido para sincronizar PostgreSQL -> SQLite

echo "======================================"
echo "  PostgreSQL → SQLite"
echo "======================================"
echo ""

# Verificar se deve fazer truncate
if [ "$1" == "--truncate" ]; then
    echo "⚠️  Modo: TRUNCATE (limpando dados existentes)"
    php sync_database.php --from=postgresql --to=sqlite --truncate
else
    echo "ℹ️  Modo: MERGE (preservando dados existentes)"
    echo "   Use '$0 --truncate' para limpar antes"
    php sync_database.php --from=postgresql --to=sqlite
fi



