<?php
/**
 * Sistema de Auto-Instalação
 * Verifica se o banco de dados existe e cria automaticamente se necessário
 */

function autoInstallDatabase() {
    $configFile = __DIR__ . '/../config/database.php';
    $dbFile = __DIR__ . '/../config/estudos.db';

    // Se o arquivo de configuração não existe, criar do exemplo
    if (!file_exists($configFile)) {
        $exampleFile = __DIR__ . '/../config/database.example.php';
        if (file_exists($exampleFile)) {
            $content = file_get_contents($exampleFile);
            // Configurar para SQLite por padrão
            $content = str_replace(
                "define('DB_TYPE', 'postgresql');",
                "define('DB_TYPE', 'sqlite');",
                $content
            );
            file_put_contents($configFile, $content);
        }
    }

    // Se o banco SQLite não existe, criar
    if (!file_exists($dbFile)) {
        createDatabase($dbFile);
    }
}

function createDatabase($dbPath) {
    try {
        // Criar conexão SQLite
        $pdo = new PDO("sqlite:" . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Habilitar chaves estrangeiras
        $pdo->exec("PRAGMA foreign_keys = ON");

        // Iniciar transação
        $pdo->beginTransaction();

        // Criar tabelas
        createTables($pdo);

        // Inserir dados iniciais
        insertInitialData($pdo);

        // Commit
        $pdo->commit();

        error_log("Banco de dados criado com sucesso: " . $dbPath);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao criar banco de dados: " . $e->getMessage());
        throw $e;
    }
}

function createTables($pdo) {
    // Tabela usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tabela categorias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tabela cursos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            categoria_id INTEGER NOT NULL,
            imagem_capa TEXT DEFAULT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            arquivado BOOLEAN DEFAULT FALSE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        )
    ");

    // Tabela aulas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aulas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            url_video TEXT,
            ordem INTEGER DEFAULT 1,
            duracao_minutos INTEGER DEFAULT 30,
            curso_id INTEGER NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (curso_id) REFERENCES cursos(id)
        )
    ");

    // Tabela anotacoes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS anotacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            aula_id INTEGER NOT NULL,
            conteudo TEXT NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (aula_id) REFERENCES aulas(id),
            UNIQUE(usuario_id, aula_id)
        )
    ");

    // Tabela comentarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comentarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            aula_id INTEGER NOT NULL,
            conteudo TEXT NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (aula_id) REFERENCES aulas(id)
        )
    ");

    // Tabela progresso_aulas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS progresso_aulas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            aula_id INTEGER NOT NULL,
            concluida BOOLEAN DEFAULT FALSE,
            data_conclusao DATETIME,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (aula_id) REFERENCES aulas(id),
            UNIQUE(usuario_id, aula_id)
        )
    ");

    // Tabela materiais_complementares
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS materiais_complementares (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            aula_id INTEGER NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            url_arquivo TEXT NOT NULL,
            tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('pdf', 'doc', 'ppt', 'video', 'link', 'imagem', 'outro')),
            tamanho_arquivo INTEGER,
            nome_arquivo VARCHAR(255),
            ordem INTEGER DEFAULT 1,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (aula_id) REFERENCES aulas(id)
        )
    ");

    // Tabelas de simulados
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS simulados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT,
            disciplina TEXT,
            tempo_limite INTEGER,
            numero_questoes INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_simulados_disciplina ON simulados(disciplina)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_simulados_ativo ON simulados(ativo)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS simulado_questoes (
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
            texto_apoio TEXT,
            nivel_dificuldade TEXT DEFAULT 'medio',
            tags TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_questoes_simulado ON simulado_questoes(simulado_id)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS simulado_respostas (
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
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_respostas_usuario ON simulado_respostas(usuario_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_respostas_simulado ON simulado_respostas(simulado_id)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS simulado_tentativas (
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
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tentativas_usuario ON simulado_tentativas(usuario_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tentativas_simulado ON simulado_tentativas(simulado_id)");

    // Adicionar coluna analise_ia_desempenho se não existir
    try {
        $pdo->exec("ALTER TABLE simulado_tentativas ADD COLUMN analise_ia_desempenho TEXT");
    } catch (PDOException $e) {
        // Coluna já existe, ignorar erro
    }

    // Tabelas de inglês
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingles_anotacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            titulo VARCHAR(255),
            conteudo TEXT NOT NULL,
            categoria VARCHAR(50) CHECK (categoria IN ('vocabulario', 'gramatica', 'expressoes', 'pronuncia', 'outros')),
            tags TEXT,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingles_diario (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            data_entrada DATE NOT NULL,
            conteudo TEXT NOT NULL,
            humor VARCHAR(20) CHECK (humor IN ('otimo', 'bom', 'neutro', 'ruim', 'pessimo')),
            tags TEXT,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            UNIQUE(usuario_id, data_entrada)
        )
    ");

    // Tabelas de lições de inglês geradas por IA
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingles_licoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            tema TEXT NOT NULL,
            titulo TEXT NOT NULL,
            descricao TEXT,
            nivel TEXT CHECK (nivel IN ('basico', 'intermediario', 'avancado')) DEFAULT 'intermediario',
            conteudo_apoio TEXT,
            numero_questoes INTEGER DEFAULT 9,
            ativo INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_licoes_usuario ON ingles_licoes(usuario_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_licoes_ativo ON ingles_licoes(ativo)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingles_licao_questoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            licao_id INTEGER NOT NULL,
            numero_questao INTEGER NOT NULL,
            tipo_questao TEXT NOT NULL CHECK (tipo_questao IN ('multipla_escolha', 'escrita', 'preencher_lacuna')),
            enunciado TEXT NOT NULL,
            contexto TEXT,
            alternativa_a TEXT,
            alternativa_b TEXT,
            alternativa_c TEXT,
            alternativa_d TEXT,
            resposta_correta_multipla TEXT,
            texto_com_lacuna TEXT,
            resposta_correta_lacuna TEXT,
            respostas_aceitas TEXT,
            prompt_escrita TEXT,
            criterios_avaliacao TEXT,
            explicacao TEXT,
            dicas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (licao_id) REFERENCES ingles_licoes(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_licao_questoes_licao ON ingles_licao_questoes(licao_id)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingles_licao_tentativas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            licao_id INTEGER NOT NULL,
            data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_fim TIMESTAMP,
            nota REAL,
            questoes_corretas INTEGER DEFAULT 0,
            questoes_totais INTEGER DEFAULT 9,
            finalizado INTEGER DEFAULT 0,
            feedback_geral TEXT,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (licao_id) REFERENCES ingles_licoes(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_licao_tentativas_usuario ON ingles_licao_tentativas(usuario_id)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingles_licao_respostas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            licao_id INTEGER NOT NULL,
            questao_id INTEGER NOT NULL,
            tentativa_id INTEGER NOT NULL,
            tipo_questao TEXT NOT NULL,
            resposta_multipla TEXT,
            resposta_lacuna TEXT,
            resposta_escrita TEXT,
            correta INTEGER NOT NULL,
            pontuacao REAL,
            analise_ia TEXT,
            tempo_resposta INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (licao_id) REFERENCES ingles_licoes(id) ON DELETE CASCADE,
            FOREIGN KEY (questao_id) REFERENCES ingles_licao_questoes(id) ON DELETE CASCADE,
            FOREIGN KEY (tentativa_id) REFERENCES ingles_licao_tentativas(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_licao_respostas_tentativa ON ingles_licao_respostas(tentativa_id)");

    // Tabela de certificados
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS certificados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            curso_id INTEGER NOT NULL,
            codigo_validacao TEXT NOT NULL UNIQUE,
            data_conclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            progresso_percentual REAL DEFAULT 100,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (curso_id) REFERENCES cursos(id)
        )
    ");

    // Tabela de configurações do sistema
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT,
            descricao TEXT,
            tipo VARCHAR(50) DEFAULT 'text',
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tabela de certificados externos (outras plataformas)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS certificados_externos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            instituicao VARCHAR(255) NOT NULL,
            categoria VARCHAR(50) NOT NULL CHECK (categoria IN ('graduacao', 'pos_mba', 'extensao', 'curso_livre')),
            descricao TEXT,
            data_conclusao DATE,
            carga_horaria INTEGER,
            arquivo_certificado TEXT,
            url_verificacao TEXT,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    // Tabela de cursos arquivados por usuário
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cursos_arquivados (
            usuario_id INTEGER NOT NULL,
            curso_id INTEGER NOT NULL,
            data_arquivamento DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (usuario_id, curso_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
        )
    ");

    // Tabela de cursos favoritos por usuário
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cursos_favoritos (
            usuario_id INTEGER NOT NULL,
            curso_id INTEGER NOT NULL,
            data_favoritado DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (usuario_id, curso_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
        )
    ");
}

function insertInitialData($pdo) {
    // Inserir usuário admin
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, is_admin, ativo)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute(['Administrador', 'admin@teste.com', $adminPassword, 1, 1]);

    // Inserir categorias
    $categorias = [
        ['Programação', 'Cursos de programação e desenvolvimento'],
        ['Design', 'Cursos de design gráfico e UI/UX'],
        ['Marketing', 'Cursos de marketing digital e estratégias'],
        ['Idiomas', 'Cursos de idiomas e comunicação']
    ];

    $stmt = $pdo->prepare("INSERT INTO categorias (nome, descricao, ativo) VALUES (?, ?, ?)");
    foreach ($categorias as $cat) {
        $stmt->execute([$cat[0], $cat[1], 1]);
    }

    // Inserir cursos de exemplo
    $cursos = [
        ['JavaScript Fundamentos', 'Aprenda JavaScript do zero', 1],
        ['Python para Iniciantes', 'Introdução à programação com Python', 1],
        ['Design UI/UX', 'Princípios de design de interface', 2],
        ['Inglês Básico', 'Curso básico de inglês', 4]
    ];

    $stmt = $pdo->prepare("INSERT INTO cursos (titulo, descricao, categoria_id, ativo) VALUES (?, ?, ?, ?)");
    foreach ($cursos as $curso) {
        $stmt->execute([$curso[0], $curso[1], $curso[2], 1]);
    }

    // Inserir configurações padrão do sistema
    $configuracoes = [
        ['ai_provider', 'gemini', 'Provedor de IA (openai, gemini, groq)', 'select'],
        ['openai_api_key', '', 'Chave da API OpenAI', 'password'],
        ['openai_model', 'gpt-4o-mini', 'Modelo OpenAI', 'text'],
        ['gemini_api_key', '', 'Chave da API Google Gemini', 'password'],
        ['gemini_model', 'gemini-2.5-flash', 'Modelo Gemini', 'text'],
        ['groq_api_key', '', 'Chave da API Groq', 'password'],
        ['groq_model', 'llama-3.1-8b-instant', 'Modelo Groq', 'text'],
        ['ai_temperature', '0.3', 'Temperatura (0.0-1.0)', 'number'],
        ['ai_max_tokens', '4000', 'Máximo de tokens', 'number'],
        ['youtube_api_key', '', 'Chave da API YouTube Data v3', 'password']
    ];

    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES (?, ?, ?, ?)");
    foreach ($configuracoes as $config) {
        $stmt->execute([$config[0], $config[1], $config[2], $config[3]]);
    }
}

function runMigrations() {
    try {
        $dbFile = __DIR__ . '/../config/estudos.db';

        // Se o banco não existe, não precisa migrar
        if (!file_exists($dbFile)) {
            return;
        }

        $pdo = new PDO("sqlite:" . $dbFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Verificar se a tabela simulado_questoes existe
        $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='simulado_questoes'");
        if (!$tableCheck->fetch()) {
            // Tabela não existe, não precisa migrar
            return;
        }

        // Verificar se a coluna texto_apoio existe na tabela simulado_questoes
        $query = $pdo->query("PRAGMA table_info(simulado_questoes)");
        $columns = $query->fetchAll(PDO::FETCH_ASSOC);
        $hasTextoApoio = false;

        foreach ($columns as $column) {
            if ($column['name'] === 'texto_apoio') {
                $hasTextoApoio = true;
                break;
            }
        }

        // Se não existe, adicionar a coluna
        if (!$hasTextoApoio) {
            $pdo->exec("ALTER TABLE simulado_questoes ADD COLUMN texto_apoio TEXT");
        }

    } catch (Exception $e) {
        // Ignorar erros de migração para não quebrar o sistema
        error_log("Erro na migração: " . $e->getMessage());
    }
}

// Executar auto-instalação
autoInstallDatabase();

// Executar migrações
runMigrations();
?>
