#!/bin/bash

echo "=== INSTALAÇÃO DO DRIVER SQLITE3 PARA PHP ==="
echo ""

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script precisa ser executado como root (sudo)"
    echo "Execute: sudo ./install_sqlite.sh"
    exit 1
fi

echo "🔍 Verificando sistema operacional..."

# Detectar distribuição
if [ -f /etc/debian_version ]; then
    DISTRO="debian"
    echo "✅ Sistema detectado: Debian/Ubuntu"
elif [ -f /etc/redhat-release ]; then
    DISTRO="redhat"
    echo "✅ Sistema detectado: RedHat/CentOS"
else
    echo "❌ Sistema operacional não suportado"
    exit 1
fi

echo ""
echo "📦 Instalando driver SQLite3..."

if [ "$DISTRO" = "debian" ]; then
    # Debian/Ubuntu
    apt-get update
    apt-get install -y php-sqlite3 php-pdo-sqlite
    
    # Verificar se foi instalado
    if php -m | grep -q "pdo_sqlite"; then
        echo "✅ Driver SQLite3 instalado com sucesso!"
    else
        echo "❌ Falha na instalação do driver SQLite3"
        exit 1
    fi
    
elif [ "$DISTRO" = "redhat" ]; then
    # RedHat/CentOS
    yum install -y php-pdo_sqlite
    
    # Verificar se foi instalado
    if php -m | grep -q "pdo_sqlite"; then
        echo "✅ Driver SQLite3 instalado com sucesso!"
    else
        echo "❌ Falha na instalação do driver SQLite3"
        exit 1
    fi
fi

echo ""
echo "🔄 Reiniciando serviços..."

# Reiniciar Apache/Nginx se estiver rodando
if systemctl is-active --quiet apache2; then
    systemctl restart apache2
    echo "✅ Apache reiniciado"
elif systemctl is-active --quiet httpd; then
    systemctl restart httpd
    echo "✅ Apache reiniciado"
fi

if systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo "✅ Nginx reiniciado"
fi

echo ""
echo "🧪 Testando instalação..."

# Testar se o driver está funcionando
php -r "
if (extension_loaded('pdo_sqlite')) {
    echo '✅ Driver PDO SQLite carregado com sucesso!' . PHP_EOL;
    try {
        \$pdo = new PDO('sqlite::memory:');
        echo '✅ Conexão SQLite funcionando!' . PHP_EOL;
    } catch (Exception \$e) {
        echo '❌ Erro na conexão SQLite: ' . \$e->getMessage() . PHP_EOL;
    }
} else {
    echo '❌ Driver PDO SQLite não está carregado' . PHP_EOL;
}
"

echo ""
echo "🎉 Instalação concluída!"
echo ""
echo "📋 Próximos passos:"
echo "1. Altere DB_TYPE para 'sqlite' no arquivo config/database.php"
echo "2. Execute: php setup_sqlite.php"
echo "3. Execute: php test_database_config.php"
echo ""
echo "💡 Para voltar ao PostgreSQL, altere DB_TYPE para 'postgresql'"
