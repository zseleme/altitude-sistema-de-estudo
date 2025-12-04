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

    echo "<div class='success'>Conexão estabelecida com sucesso!</div>";

    // Tabela de simulados
    $query = "CREATE TABLE IF NOT EXISTS simulados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT,
        disciplina VARCHAR(100),
        tempo_limite INT COMMENT 'Tempo em minutos',
        numero_questoes INT DEFAULT 0,
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_disciplina (disciplina),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($query);
    echo "<div class='success'>✓ Tabela 'simulados' criada com sucesso!</div>";

    // Tabela de questões
    echo "<div class='info'>Criando tabela 'simulado_questoes'...</div>";
    $query = "CREATE TABLE IF NOT EXISTS simulado_questoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        simulado_id INT NOT NULL,
        numero_questao INT NOT NULL,
        enunciado TEXT NOT NULL,
        alternativa_a TEXT NOT NULL,
        alternativa_b TEXT NOT NULL,
        alternativa_c TEXT NOT NULL,
        alternativa_d TEXT NOT NULL,
        alternativa_e TEXT,
        resposta_correta CHAR(1) NOT NULL,
        explicacao TEXT COMMENT 'Explicação da resposta correta',
        nivel_dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
        tags VARCHAR(255) COMMENT 'Tags separadas por vírgula',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE,
        INDEX idx_simulado (simulado_id),
        INDEX idx_nivel (nivel_dificuldade)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($query);
    echo "<div class='success'>✓ Tabela 'simulado_questoes' criada com sucesso!</div>";

    // Tabela de respostas dos alunos
    echo "<div class='info'>Criando tabela 'simulado_respostas'...</div>";
    $query = "CREATE TABLE IF NOT EXISTS simulado_respostas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        simulado_id INT NOT NULL,
        questao_id INT NOT NULL,
        resposta_usuario CHAR(1),
        correta TINYINT(1) NOT NULL,
        analise_ia TEXT COMMENT 'Análise da IA para respostas incorretas',
        tempo_resposta INT COMMENT 'Tempo em segundos',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE,
        FOREIGN KEY (questao_id) REFERENCES simulado_questoes(id) ON DELETE CASCADE,
        INDEX idx_usuario (usuario_id),
        INDEX idx_simulado (simulado_id),
        INDEX idx_usuario_simulado (usuario_id, simulado_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($query);
    echo "<div class='success'>✓ Tabela 'simulado_respostas' criada com sucesso!</div>";

    // Tabela de tentativas (para rastrear quando o usuário iniciou/finalizou um simulado)
    echo "<div class='info'>Criando tabela 'simulado_tentativas'...</div>";
    $query = "CREATE TABLE IF NOT EXISTS simulado_tentativas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        simulado_id INT NOT NULL,
        data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_fim TIMESTAMP NULL,
        nota DECIMAL(5,2) COMMENT 'Nota final em percentual',
        questoes_corretas INT DEFAULT 0,
        questoes_totais INT DEFAULT 0,
        finalizado TINYINT(1) DEFAULT 0,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE,
        INDEX idx_usuario (usuario_id),
        INDEX idx_simulado (simulado_id),
        INDEX idx_finalizado (finalizado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($query);
    echo "<div class='success'>✓ Tabela 'simulado_tentativas' criada com sucesso!</div>";

    echo "<div class='success' style='font-size: 18px; font-weight: bold; margin-top: 20px;'>
        ✓ Migração concluída com sucesso!<br>
        Todas as 4 tabelas foram criadas.
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
