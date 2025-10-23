<?php
/**
 * Script de Sincronização de Banco de Dados
 * Sincroniza dados entre PostgreSQL e SQLite
 * 
 * Uso:
 *   php sync_database.php --from=postgresql --to=sqlite
 *   php sync_database.php --from=sqlite --to=postgresql
 */

require_once __DIR__ . '/config/database.php';

// ========================================
// Configuração
// ========================================

$options = getopt('', ['from:', 'to:', 'tables:', 'truncate', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

// Validar parâmetros
$from = $options['from'] ?? null;
$to = $options['to'] ?? null;
$specificTables = isset($options['tables']) ? explode(',', $options['tables']) : null;
$truncate = isset($options['truncate']);

if (!$from || !$to) {
    echo "❌ Erro: Parâmetros --from e --to são obrigatórios\n\n";
    showHelp();
    exit(1);
}

if (!in_array($from, ['postgresql', 'sqlite']) || !in_array($to, ['postgresql', 'sqlite'])) {
    echo "❌ Erro: Valores válidos são 'postgresql' ou 'sqlite'\n\n";
    showHelp();
    exit(1);
}

if ($from === $to) {
    echo "❌ Erro: Origem e destino não podem ser iguais\n\n";
    exit(1);
}

// ========================================
// Tabelas e Ordem (respeitando FKs)
// ========================================

$tables = [
    'usuarios' => [
        'columns' => ['id', 'nome', 'email', 'senha', 'is_admin', 'ativo', 'data_criacao'],
        'pk' => 'id'
    ],
    'categorias' => [
        'columns' => ['id', 'nome', 'descricao', 'ativo', 'data_criacao'],
        'pk' => 'id'
    ],
    'cursos' => [
        'columns' => ['id', 'titulo', 'descricao', 'categoria_id', 'ativo', 'data_criacao', 'imagem_capa'],
        'pk' => 'id'
    ],
    'aulas' => [
        'columns' => ['id', 'titulo', 'descricao', 'url_video', 'ordem', 'duracao_minutos', 'curso_id', 'ativo', 'data_criacao'],
        'pk' => 'id'
    ],
    'materiais_complementares' => [
        'columns' => ['id', 'aula_id', 'titulo', 'descricao', 'url_arquivo', 'tipo', 'tamanho_arquivo', 'nome_arquivo', 'ordem', 'ativo', 'data_criacao'],
        'pk' => 'id'
    ],
    'anotacoes' => [
        'columns' => ['id', 'usuario_id', 'aula_id', 'conteudo', 'data_criacao', 'data_atualizacao'],
        'pk' => 'id'
    ],
    'comentarios' => [
        'columns' => ['id', 'usuario_id', 'aula_id', 'conteudo', 'data_criacao'],
        'pk' => 'id'
    ],
    'progresso_aulas' => [
        'columns' => ['id', 'usuario_id', 'aula_id', 'concluida', 'data_conclusao', 'data_criacao'],
        'pk' => 'id'
    ]
];

// Filtrar tabelas se especificadas
if ($specificTables) {
    $tables = array_intersect_key($tables, array_flip($specificTables));
    if (empty($tables)) {
        echo "❌ Erro: Nenhuma tabela válida especificada\n\n";
        exit(1);
    }
}

// ========================================
// Conexões
// ========================================

echo "=== SINCRONIZAÇÃO DE BANCO DE DADOS ===\n\n";
echo "📊 Origem: " . strtoupper($from) . "\n";
echo "📊 Destino: " . strtoupper($to) . "\n";
echo "📋 Tabelas: " . count($tables) . " tabela(s)\n";
echo "🗑️  Truncar: " . ($truncate ? 'SIM' : 'NÃO') . "\n\n";

try {
    // Conexão de origem
    echo "🔌 Conectando ao banco de origem ($from)...\n";
    $sourceDb = connectToDatabase($from);
    echo "✅ Conectado ao banco de origem\n\n";
    
    // Conexão de destino
    echo "🔌 Conectando ao banco de destino ($to)...\n";
    $destDb = connectToDatabase($to);
    echo "✅ Conectado ao banco de destino\n\n";
    
    // ========================================
    // Processo de Sincronização
    // ========================================
    
    $totalRecords = 0;
    $startTime = microtime(true);
    
    foreach ($tables as $tableName => $tableConfig) {
        echo "📦 Processando tabela: $tableName\n";
        
        try {
            // Verificar se a tabela existe na origem
            if (!tableExists($sourceDb, $tableName, $from)) {
                echo "   ⚠️  Tabela não existe no banco de origem, pulando...\n\n";
                continue;
            }
            
            // Contar registros na origem
            $countResult = $sourceDb->query("SELECT COUNT(*) as total FROM $tableName");
            $count = $countResult->fetchColumn();
            
            if ($count == 0) {
                echo "   ℹ️  Tabela vazia, pulando...\n\n";
                continue;
            }
            
            echo "   📊 Registros encontrados: $count\n";
            
            // Truncar tabela de destino se solicitado
            if ($truncate) {
                echo "   🗑️  Truncando tabela de destino...\n";
                if ($to === 'postgresql') {
                    $destDb->exec("TRUNCATE TABLE $tableName RESTART IDENTITY CASCADE");
                } else {
                    // SQLite: desabilitar FKs temporariamente para truncate
                    $destDb->exec("PRAGMA foreign_keys = OFF");
                    $destDb->exec("DELETE FROM $tableName");
                    $destDb->exec("DELETE FROM sqlite_sequence WHERE name='$tableName'");
                    $destDb->exec("PRAGMA foreign_keys = ON");
                }
            }
            
            // Buscar dados da origem
            $columns = implode(', ', $tableConfig['columns']);
            $stmt = $sourceDb->query("SELECT $columns FROM $tableName ORDER BY {$tableConfig['pk']}");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Inserir no destino
            $inserted = 0;
            $updated = 0;
            $errors = 0;
            
            $destDb->beginTransaction();
            
            foreach ($rows as $row) {
                try {
                    // Preparar valores
                    $columnNames = array_keys($row);
                    $placeholders = array_fill(0, count($columnNames), '?');
                    
                    // Tentar inserir
                    $insertSql = "INSERT INTO $tableName (" . implode(', ', $columnNames) . ") 
                                  VALUES (" . implode(', ', $placeholders) . ")";
                    
                    // Se não for truncate, tentar update se já existir
                    if (!$truncate) {
                        // Verificar se existe
                        $pk = $tableConfig['pk'];
                        $pkValue = $row[$pk];
                        
                        $checkStmt = $destDb->prepare("SELECT COUNT(*) FROM $tableName WHERE $pk = ?");
                        $checkStmt->execute([$pkValue]);
                        $exists = $checkStmt->fetchColumn() > 0;
                        
                        if ($exists) {
                            // UPDATE
                            $updateParts = [];
                            $updateValues = [];
                            foreach ($columnNames as $col) {
                                if ($col !== $pk) {
                                    $updateParts[] = "$col = ?";
                                    $updateValues[] = $row[$col];
                                }
                            }
                            $updateValues[] = $pkValue;
                            
                            $updateSql = "UPDATE $tableName SET " . implode(', ', $updateParts) . " WHERE $pk = ?";
                            $updateStmt = $destDb->prepare($updateSql);
                            $updateStmt->execute($updateValues);
                            $updated++;
                            continue;
                        }
                    }
                    
                    // INSERT
                    $insertStmt = $destDb->prepare($insertSql);
                    $insertStmt->execute(array_values($row));
                    $inserted++;
                    
                } catch (PDOException $e) {
                    $errors++;
                    if ($errors <= 3) {
                        echo "   ⚠️  Erro ao inserir registro ID {$row[$tableConfig['pk']]}: " . $e->getMessage() . "\n";
                    }
                }
            }
            
            $destDb->commit();
            
            echo "   ✅ Inseridos: $inserted | Atualizados: $updated | Erros: $errors\n";
            $totalRecords += $inserted + $updated;
            
        } catch (Exception $e) {
            echo "   ❌ Erro ao processar tabela: " . $e->getMessage() . "\n";
            if (isset($destDb) && $destDb->inTransaction()) {
                $destDb->rollBack();
            }
        }
        
        echo "\n";
    }
    
    // ========================================
    // Resumo Final
    // ========================================
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "========================================\n";
    echo "✅ SINCRONIZAÇÃO CONCLUÍDA!\n";
    echo "========================================\n";
    echo "📊 Total de registros: $totalRecords\n";
    echo "⏱️  Tempo de execução: {$duration}s\n";
    echo "📅 Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    echo "\n";
    
    // Verificar contagens finais
    echo "📋 Contagem final de registros:\n";
    foreach ($tables as $tableName => $tableConfig) {
        if (!tableExists($sourceDb, $tableName, $from)) {
            echo "   ⊘  $tableName: Não existe na origem\n";
            continue;
        }
        
        $sourceCount = $sourceDb->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
        $destCount = $destDb->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
        $status = $sourceCount == $destCount ? '✅' : '⚠️';
        echo "   $status $tableName: Origem=$sourceCount | Destino=$destCount\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// ========================================
// Funções Auxiliares
// ========================================

function tableExists($pdo, $tableName, $dbType) {
    try {
        if ($dbType === 'postgresql') {
            $stmt = $pdo->prepare("SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            )");
            $stmt->execute([DB_SCHEMA, $tableName]);
            return $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$tableName]);
            return $stmt->fetch() !== false;
        }
    } catch (Exception $e) {
        return false;
    }
}

function connectToDatabase($type) {
    if ($type === 'postgresql') {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("SET search_path TO " . DB_SCHEMA);
        return $pdo;
    } else {
        // Para SQLite, usar o banco da pasta config
        $sqlitePath = __DIR__ . '/config/estudos.db';
        if (!file_exists($sqlitePath)) {
            throw new Exception("Arquivo SQLite não encontrado: $sqlitePath");
        }
        $pdo = new PDO("sqlite:$sqlitePath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON");
        return $pdo;
    }
}

function showHelp() {
    echo <<<HELP
=== SCRIPT DE SINCRONIZAÇÃO DE BANCO DE DADOS ===

DESCRIÇÃO:
  Sincroniza dados entre PostgreSQL e SQLite em ambas as direções.

USO:
  php sync_database.php --from=ORIGEM --to=DESTINO [OPÇÕES]

PARÂMETROS OBRIGATÓRIOS:
  --from=ORIGEM      Banco de origem (postgresql ou sqlite)
  --to=DESTINO       Banco de destino (postgresql ou sqlite)

OPÇÕES:
  --tables=TABELAS   Sincronizar apenas tabelas específicas (separadas por vírgula)
                     Exemplo: --tables=usuarios,cursos,aulas
  
  --truncate         Limpar tabelas de destino antes de sincronizar
                     (usa TRUNCATE para melhor performance)
  
  --help             Mostrar esta ajuda

EXEMPLOS:
  # Sincronizar tudo do PostgreSQL para SQLite (preservando dados existentes)
  php sync_database.php --from=postgresql --to=sqlite
  
  # Sincronizar do SQLite para PostgreSQL (limpando destino)
  php sync_database.php --from=sqlite --to=postgresql --truncate
  
  # Sincronizar apenas algumas tabelas
  php sync_database.php --from=postgresql --to=sqlite --tables=usuarios,cursos
  
  # Sincronizar tudo, sobrescrevendo destino
  php sync_database.php --from=postgresql --to=sqlite --truncate

TABELAS SUPORTADAS (em ordem de sincronização):
  1. usuarios
  2. categorias
  3. cursos
  4. aulas
  5. materiais_complementares
  6. anotacoes
  7. comentarios
  8. progresso_aulas

NOTAS:
  • A sincronização respeita a ordem das chaves estrangeiras
  • Se --truncate não for usado, registros existentes serão atualizados
  • Sempre faça backup antes de sincronizar!
  • O script exibe progresso detalhado durante a execução

HELP;
}

?>

