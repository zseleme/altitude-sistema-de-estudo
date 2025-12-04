<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

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
    echo "Tabela 'simulados' criada com sucesso!\n";

    // Tabela de questões
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
    echo "Tabela 'simulado_questoes' criada com sucesso!\n";

    // Tabela de respostas dos alunos
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
    echo "Tabela 'simulado_respostas' criada com sucesso!\n";

    // Tabela de tentativas (para rastrear quando o usuário iniciou/finalizou um simulado)
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
    echo "Tabela 'simulado_tentativas' criada com sucesso!\n";

    echo "\nMigração concluída com sucesso!\n";

} catch(PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
