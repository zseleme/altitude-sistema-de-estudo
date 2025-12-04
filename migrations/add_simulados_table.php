<?php
// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Migração - Simulados</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .success { color: green; padding: 10px; background: #d4edda; margin: 10px 0; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #f8d7da; margin: 10px 0; border-radius: 5px; }
        .info { padding: 10px; background: #d1ecf1; margin: 10px 0; border-radius: 5px; }
        h1 { color: #333; }
    </style>
</head>
<body>
<h1>Migração do Sistema de Simulados</h1>";

try {
    require_once __DIR__ . '/../config/database.php';

    echo "<div class='info'>Conectando ao banco de dados...</div>";

    $database = Database::getInstance();
    $db = $database->getConnection();
    $isPostgres = $database->isPostgreSQL();

    echo "<div class='success'>Conexão estabelecida com sucesso! (Banco: " . ($isPostgres ? "PostgreSQL" : "SQLite") . ")</div>";

    // Tabela de simulados
    if ($isPostgres) {
        $query = "CREATE TABLE IF NOT EXISTS simulados (
            id SERIAL PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            disciplina VARCHAR(100),
            tempo_limite INTEGER,
            numero_questoes INTEGER DEFAULT 0,
            ativo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    } else {
        $query = "CREATE TABLE IF NOT EXISTS simulados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT,
            disciplina TEXT,
            tempo_limite INTEGER,
            numero_questoes INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    }

    $db->exec($query);

    // Criar índices separadamente
    if ($isPostgres) {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_simulados_disciplina ON simulados(disciplina)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_simulados_ativo ON simulados(ativo)");
    } else {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_simulados_disciplina ON simulados(disciplina)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_simulados_ativo ON simulados(ativo)");
    }

    echo "<div class='success'>✓ Tabela 'simulados' criada com sucesso!</div>";

    // Tabela de questões
    echo "<div class='info'>Criando tabela 'simulado_questoes'...</div>";

    if ($isPostgres) {
        $query = "CREATE TABLE IF NOT EXISTS simulado_questoes (
            id SERIAL PRIMARY KEY,
            simulado_id INTEGER NOT NULL,
            numero_questao INTEGER NOT NULL,
            enunciado TEXT NOT NULL,
            alternativa_a TEXT NOT NULL,
            alternativa_b TEXT NOT NULL,
            alternativa_c TEXT NOT NULL,
            alternativa_d TEXT NOT NULL,
            alternativa_e TEXT,
            resposta_correta CHAR(1) NOT NULL,
            explicacao TEXT,
            nivel_dificuldade VARCHAR(20) DEFAULT 'medio',
            tags VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE
        )";
    } else {
        $query = "CREATE TABLE IF NOT EXISTS simulado_questoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            simulado_id INTEGER NOT NULL,
            numero_questao INTEGER NOT NULL,
            enunciado TEXT NOT NULL,
            alternativa_a TEXT NOT NULL,
            alternativa_b TEXT NOT NULL,
            alternativa_c TEXT NOT NULL,
            alternativa_d TEXT NOT NULL,
            alternativa_e TEXT,
            resposta_correta TEXT NOT NULL,
            explicacao TEXT,
            nivel_dificuldade TEXT DEFAULT 'medio',
            tags TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE
        )";
    }

    $db->exec($query);

    // Criar índices
    $db->exec("CREATE INDEX IF NOT EXISTS idx_questoes_simulado ON simulado_questoes(simulado_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_questoes_nivel ON simulado_questoes(nivel_dificuldade)");

    echo "<div class='success'>✓ Tabela 'simulado_questoes' criada com sucesso!</div>";

    // Tabela de respostas dos alunos
    echo "<div class='info'>Criando tabela 'simulado_respostas'...</div>";

    if ($isPostgres) {
        $query = "CREATE TABLE IF NOT EXISTS simulado_respostas (
            id SERIAL PRIMARY KEY,
            usuario_id INTEGER NOT NULL,
            simulado_id INTEGER NOT NULL,
            questao_id INTEGER NOT NULL,
            resposta_usuario CHAR(1),
            correta BOOLEAN NOT NULL,
            analise_ia TEXT,
            tempo_resposta INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE,
            FOREIGN KEY (questao_id) REFERENCES simulado_questoes(id) ON DELETE CASCADE
        )";
    } else {
        $query = "CREATE TABLE IF NOT EXISTS simulado_respostas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            simulado_id INTEGER NOT NULL,
            questao_id INTEGER NOT NULL,
            resposta_usuario TEXT,
            correta INTEGER NOT NULL,
            analise_ia TEXT,
            tempo_resposta INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE,
            FOREIGN KEY (questao_id) REFERENCES simulado_questoes(id) ON DELETE CASCADE
        )";
    }

    $db->exec($query);

    // Criar índices
    $db->exec("CREATE INDEX IF NOT EXISTS idx_respostas_usuario ON simulado_respostas(usuario_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_respostas_simulado ON simulado_respostas(simulado_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_respostas_usuario_simulado ON simulado_respostas(usuario_id, simulado_id)");

    echo "<div class='success'>✓ Tabela 'simulado_respostas' criada com sucesso!</div>";

    // Tabela de tentativas
    echo "<div class='info'>Criando tabela 'simulado_tentativas'...</div>";

    if ($isPostgres) {
        $query = "CREATE TABLE IF NOT EXISTS simulado_tentativas (
            id SERIAL PRIMARY KEY,
            usuario_id INTEGER NOT NULL,
            simulado_id INTEGER NOT NULL,
            data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_fim TIMESTAMP,
            nota DECIMAL(5,2),
            questoes_corretas INTEGER DEFAULT 0,
            questoes_totais INTEGER DEFAULT 0,
            finalizado BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE
        )";
    } else {
        $query = "CREATE TABLE IF NOT EXISTS simulado_tentativas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            simulado_id INTEGER NOT NULL,
            data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_fim TIMESTAMP,
            nota REAL,
            questoes_corretas INTEGER DEFAULT 0,
            questoes_totais INTEGER DEFAULT 0,
            finalizado INTEGER DEFAULT 0,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE
        )";
    }

    $db->exec($query);

    // Criar índices
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tentativas_usuario ON simulado_tentativas(usuario_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tentativas_simulado ON simulado_tentativas(simulado_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tentativas_finalizado ON simulado_tentativas(finalizado)");

    echo "<div class='success'>✓ Tabela 'simulado_tentativas' criada com sucesso!</div>";

    echo "<div class='success' style='font-size: 18px; font-weight: bold; margin-top: 20px;'>
        ✓ Migração concluída com sucesso!<br>
        Todas as 4 tabelas foram criadas com seus índices.
    </div>";

} catch(PDOException $e) {
    echo "<div class='error'>
        <strong>Erro na migração:</strong><br>
        " . htmlspecialchars($e->getMessage()) . "
    </div>";
} catch(Exception $e) {
    echo "<div class='error'>
        <strong>Erro geral:</strong><br>
        " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "</body></html>";
