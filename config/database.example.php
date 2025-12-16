<?php
// Configuração do tipo de banco de dados
// Opções: 'postgresql' ou 'sqlite'
define('DB_TYPE', 'postgresql'); // Altere para 'sqlite' se quiser usar SQLite

if (DB_TYPE === 'postgresql') {
    // Configurações do banco de dados PostgreSQL
    define('DB_HOST', 'localhost');
    define('DB_PORT', '5432');
    define('DB_NAME', 'seu_banco');
    define('DB_USER', 'seu_usuario');
    define('DB_PASS', 'sua_senha');
    define('DB_SCHEMA', 'estudos');
} else {
    // Configurações do banco de dados SQLite
    define('DB_PATH', __DIR__ . '/estudos.db');
}

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            if (DB_TYPE === 'postgresql') {
                // Conexão PostgreSQL
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Definir o schema padrão
                $this->pdo->exec("SET search_path TO " . DB_SCHEMA);
            } else {
                // Conexão SQLite
                $this->pdo = new PDO("sqlite:" . DB_PATH, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Habilitar chaves estrangeiras no SQLite
                $this->pdo->exec("PRAGMA foreign_keys = ON");
            }
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados (" . DB_TYPE . "): " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters with proper types
        foreach ($params as $i => $param) {
            if (is_bool($param)) {
                $stmt->bindValue($i + 1, $param, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue($i + 1, $param);
            }
        }
        
        return $stmt->execute();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
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
    
    /**
     * Retorna o valor booleano TRUE compatível com o banco de dados
     * PostgreSQL: TRUE
     * SQLite: 1
     */
    public function getBoolTrue() {
        return $this->isSQLite() ? '1' : 'TRUE';
    }
    
    /**
     * Retorna o valor booleano FALSE compatível com o banco de dados
     * PostgreSQL: FALSE
     * SQLite: 0
     */
    public function getBoolFalse() {
        return $this->isSQLite() ? '0' : 'FALSE';
    }
    
    // Método para executar SQL específico do banco
    public function executeDbSpecific($postgresqlSql, $sqliteSql = null) {
        if ($sqliteSql === null) {
            $sqliteSql = $postgresqlSql;
        }
        
        $sql = $this->isPostgreSQL() ? $postgresqlSql : $sqliteSql;
        return $this->execute($sql);
    }
    
    // Método para obter o nome da tabela com schema se necessário
    public function getTableName($tableName) {
        if ($this->isPostgreSQL()) {
            return DB_SCHEMA . '.' . $tableName;
        }
        return $tableName;
    }
}