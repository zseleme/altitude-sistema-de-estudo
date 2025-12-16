<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireAdmin();

$db = Database::getInstance();
$currentDbType = DB_TYPE;
$message = '';
$messageType = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_postgres') {
        $host = trim($_POST['pg_host'] ?? '');
        $port = trim($_POST['pg_port'] ?? '');
        $dbname = trim($_POST['pg_dbname'] ?? '');
        $user = trim($_POST['pg_user'] ?? '');
        $password = $_POST['pg_password'] ?? '';
        $schema = trim($_POST['pg_schema'] ?? 'public');

        // Validar schema name para prevenir SQL injection
        // PostgreSQL schema names podem conter apenas letras, números, underscore e hífen
        // e devem começar com letra ou underscore
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $schema)) {
            $message = 'Nome do schema inválido. Use apenas letras, números, underscore e hífen, começando com letra ou underscore.';
            $messageType = 'error';
        } else {
            try {
                // Testar conexão primeiro
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                $testPdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                // Escapar o schema name para PostgreSQL (identificadores usam aspas duplas)
                // A validação regex acima garante apenas caracteres seguros, mas ainda precisamos escapar aspas duplas
                $quotedSchema = '"' . str_replace('"', '""', $schema) . '"';
                $testPdo->exec("SET search_path TO $quotedSchema");

            // Se funcionou, atualizar config/database.php
            $configContent = "<?php
// Configuração do tipo de banco de dados
// Opções: 'postgresql' ou 'sqlite'
define('DB_TYPE', 'postgresql');

if (DB_TYPE === 'postgresql') {
    // Configurações do banco de dados PostgreSQL
    define('DB_HOST', '$host');
    define('DB_PORT', '$port');
    define('DB_NAME', '$dbname');
    define('DB_USER', '$user');
    define('DB_PASS', '$password');
    define('DB_SCHEMA', '$schema');
} else {
    // Configurações do banco de dados SQLite
    define('DB_PATH', __DIR__ . '/estudos.db');
}

class Database {
    private static \$instance = null;
    private \$pdo;

    private function __construct() {
        try {
            if (DB_TYPE === 'postgresql') {
                // Conexão PostgreSQL
                \$dsn = \"pgsql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";dbname=\" . DB_NAME;
                \$this->pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Definir o schema padrão
                \$this->pdo->exec(\"SET search_path TO \" . DB_SCHEMA);
            } else {
                // Conexão SQLite
                \$this->pdo = new PDO(\"sqlite:\" . DB_PATH, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Habilitar chaves estrangeiras no SQLite
                \$this->pdo->exec(\"PRAGMA foreign_keys = ON\");
            }
        } catch (PDOException \$e) {
            die(\"Erro de conexão com o banco de dados (\" . DB_TYPE . \"): \" . \$e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    public function getConnection() {
        return \$this->pdo;
    }

    public function fetchAll(\$sql, \$params = []) {
        \$stmt = \$this->pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetchAll();
    }

    public function fetchOne(\$sql, \$params = []) {
        \$stmt = \$this->pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetch();
    }

    public function execute(\$sql, \$params = []) {
        \$stmt = \$this->pdo->prepare(\$sql);

        // Bind parameters with proper types
        foreach (\$params as \$i => \$param) {
            if (is_bool(\$param)) {
                \$stmt->bindValue(\$i + 1, \$param, PDO::PARAM_BOOL);
            } else {
                \$stmt->bindValue(\$i + 1, \$param);
            }
        }

        return \$stmt->execute();
    }

    public function lastInsertId() {
        return \$this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return \$this->pdo->beginTransaction();
    }

    public function commit() {
        return \$this->pdo->commit();
    }

    public function rollback() {
        return \$this->pdo->rollback();
    }

    // Métodos auxiliares para compatibilidade entre bancos
    public function getDbType() {
        return DB_TYPE;
    }

    public function isPostgreSQL() {
        return DB_TYPE === 'postgresql';
    }

    public function isSQLite() {
        return DB_TYPE === 'sqlite';
    }

    public function getBoolTrue() {
        return \$this->isSQLite() ? '1' : 'TRUE';
    }

    public function getBoolFalse() {
        return \$this->isSQLite() ? '0' : 'FALSE';
    }

    public function executeDbSpecific(\$postgresqlSql, \$sqliteSql = null) {
        if (\$sqliteSql === null) {
            \$sqliteSql = \$postgresqlSql;
        }

        \$sql = \$this->isPostgreSQL() ? \$postgresqlSql : \$sqliteSql;
        return \$this->execute(\$sql);
    }

    public function getTableName(\$tableName) {
        if (\$this->isPostgreSQL()) {
            return DB_SCHEMA . '.' . \$tableName;
        }
        return \$tableName;
    }
}";

            $configFile = __DIR__ . '/../config/database.php';
            if (file_put_contents($configFile, $configContent)) {
                $message = 'Configurações salvas! Faça logout e login novamente para aplicar as mudanças. Não esqueça de executar as migrações de tabelas no PostgreSQL!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao salvar arquivo de configuração';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao conectar ao PostgreSQL: ' . $e->getMessage();
            $messageType = 'error';
        }
        } // Fecha o else block da validação do schema
    } elseif ($action === 'back_to_sqlite') {
        // Voltar para SQLite
        $configContent = file_get_contents(__DIR__ . '/../config/database.example.php');
        $configContent = str_replace(
            "define('DB_TYPE', 'postgresql');",
            "define('DB_TYPE', 'sqlite');",
            $configContent
        );

        $configFile = __DIR__ . '/../config/database.php';
        if (file_put_contents($configFile, $configContent)) {
            $message = 'Configuração alterada para SQLite! Faça logout e login novamente.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao alterar configuração';
            $messageType = 'error';
        }
    }
}

$content = '
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Configurações do Banco de Dados</h1>
        <p class="text-gray-600">Gerencie o tipo de banco de dados utilizado pelo sistema</p>
    </div>

    ' . ($message ? '
    <div class="mb-6 bg-' . ($messageType === 'success' ? 'green' : 'red') . '-50 border border-' . ($messageType === 'success' ? 'green' : 'red') . '-200 text-' . ($messageType === 'success' ? 'green' : 'red') . '-800 px-4 py-3 rounded-lg">
        <i class="fas fa-' . ($messageType === 'success' ? 'check-circle' : 'exclamation-triangle') . ' mr-2"></i>
        ' . htmlspecialchars($message) . '
    </div>
    ' : '') . '

    <!-- Status Atual -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            Banco de Dados Atual
        </h2>
        <div class="flex items-center">
            <div class="h-16 w-16 bg-' . ($currentDbType === 'postgresql' ? 'blue' : 'green') . '-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-database text-3xl text-' . ($currentDbType === 'postgresql' ? 'blue' : 'green') . '-600"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">' . ($currentDbType === 'postgresql' ? 'PostgreSQL' : 'SQLite') . '</h3>
                <p class="text-gray-600">' . ($currentDbType === 'postgresql' ? 'Banco de dados profissional' : 'Banco de dados local') . '</p>
            </div>
        </div>
    </div>

    ' . ($currentDbType === 'sqlite' ? '
    <!-- Configurar PostgreSQL -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-database text-blue-600 mr-2"></i>
            Migrar para PostgreSQL
        </h2>
        <p class="text-gray-600 mb-6">Configure as credenciais do PostgreSQL para migrar seus dados</p>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-yellow-900 mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Antes de continuar:
            </h3>
            <ul class="list-disc list-inside text-yellow-800 text-sm space-y-1">
                <li>Certifique-se de que o PostgreSQL está instalado e rodando</li>
                <li>Crie um database vazio no PostgreSQL</li>
                <li>Execute as migrações após salvar (migrations/*.php)</li>
                <li>Faça backup dos seus dados SQLite antes de migrar</li>
            </ul>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save_postgres">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Host</label>
                    <input type="text" name="pg_host" value="localhost" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Porta</label>
                    <input type="text" name="pg_port" value="5432" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Database</label>
                <input type="text" name="pg_dbname" placeholder="estudos_db" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Schema (opcional)</label>
                <input type="text" name="pg_schema" value="estudos"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Usuário</label>
                <input type="text" name="pg_user" placeholder="postgres" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                <input type="password" name="pg_password" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                <i class="fas fa-save mr-2"></i>
                Salvar e Migrar para PostgreSQL
            </button>
        </form>
    </div>
    ' : '
    <!-- Voltar para SQLite -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-2">
            <i class="fas fa-undo text-green-600 mr-2"></i>
            Voltar para SQLite
        </h2>
        <p class="text-gray-600 mb-4">Se preferir, pode voltar a usar o banco SQLite local</p>

        <form method="POST">
            <input type="hidden" name="action" value="back_to_sqlite">
            <button type="submit"
                    class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                <i class="fas fa-database mr-2"></i>
                Voltar para SQLite
            </button>
        </form>
    </div>
    ') . '
</div>
';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Configurações do Banco', $content, true, true);
?>
