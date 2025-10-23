<?php
require_once __DIR__ . '/config/database.php';

echo "=== SETUP POSTGRESQL - Sistema de Estudos ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "✅ Conexão com PostgreSQL estabelecida\n";
    echo "📁 Database: " . DB_NAME . "\n";
    echo "🏢 Schema: " . DB_SCHEMA . "\n\n";
    
    // Criar schema se não existir
    echo "🔨 Criando schema...\n";
    $pdo->exec("CREATE SCHEMA IF NOT EXISTS " . DB_SCHEMA);
    echo "✅ Schema '" . DB_SCHEMA . "' criado\n\n";
    
    // Definir o schema como padrão
    $pdo->exec("SET search_path TO " . DB_SCHEMA);
    
    echo "🔨 Criando tabelas...\n";
    
    // Tabela usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'usuarios' criada\n";
    
    // Tabela categorias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'categorias' criada\n";
    
    // Tabela cursos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cursos (
            id SERIAL PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            categoria_id INTEGER REFERENCES categorias(id),
            imagem_capa VARCHAR(255) DEFAULT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'cursos' criada\n";
    
    // Tabela aulas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aulas (
            id SERIAL PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            url_video TEXT,
            ordem INTEGER DEFAULT 1,
            duracao_minutos INTEGER DEFAULT 30,
            curso_id INTEGER REFERENCES cursos(id),
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'aulas' criada\n";
    
    // Tabela anotacoes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS anotacoes (
            id SERIAL PRIMARY KEY,
            usuario_id INTEGER REFERENCES usuarios(id),
            aula_id INTEGER REFERENCES aulas(id),
            conteudo TEXT NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(usuario_id, aula_id)
        )
    ");
    echo "✅ Tabela 'anotacoes' criada\n";
    
    // Tabela comentarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comentarios (
            id SERIAL PRIMARY KEY,
            usuario_id INTEGER REFERENCES usuarios(id),
            aula_id INTEGER REFERENCES aulas(id),
            conteudo TEXT NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'comentarios' criada\n";
    
    // Tabela progresso_aulas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS progresso_aulas (
            id SERIAL PRIMARY KEY,
            usuario_id INTEGER REFERENCES usuarios(id),
            aula_id INTEGER REFERENCES aulas(id),
            concluida BOOLEAN DEFAULT FALSE,
            data_conclusao TIMESTAMP,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(usuario_id, aula_id)
        )
    ");
    echo "✅ Tabela 'progresso_aulas' criada\n";
    
    // Tabela materiais_complementares
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS materiais_complementares (
            id SERIAL PRIMARY KEY,
            aula_id INTEGER REFERENCES aulas(id),
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            url_arquivo TEXT NOT NULL,
            tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('pdf', 'doc', 'ppt', 'video', 'link', 'imagem', 'outro')),
            tamanho_arquivo INTEGER,
            nome_arquivo VARCHAR(255),
            ordem INTEGER DEFAULT 1,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'materiais_complementares' criada\n";
    
    echo "\n🔍 Verificando se já existem dados...\n";
    
    // Verificar se já existem dados
    $userCount = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    
    if ($userCount == 0) {
        echo "📝 Inserindo dados iniciais...\n";
        
        // Inserir usuário admin
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO usuarios (nome, email, senha, is_admin, ativo) 
            VALUES ('Administrador', 'admin@teste.com', '$adminPassword', TRUE, TRUE)
        ");
        echo "✅ Usuário admin criado (admin@teste.com / admin123)\n";
        
        // Inserir categorias
        $categorias = [
            ['Programação', 'Cursos de programação e desenvolvimento'],
            ['Design', 'Cursos de design gráfico e UI/UX'], 
            ['Marketing', 'Cursos de marketing digital e estratégias'],
            ['Negócios', 'Cursos de gestão e empreendedorismo']
        ];
        
        foreach ($categorias as $cat) {
            $pdo->prepare("INSERT INTO categorias (nome, descricao, ativo) VALUES (?, ?, TRUE)")
                ->execute($cat);
        }
        echo "✅ Categorias criadas: " . count($categorias) . " categorias\n";
        
        // Inserir cursos
        $cursos = [
            ['JavaScript Básico', 'Aprenda os fundamentos do JavaScript desde o zero', 1],
            ['Python para Iniciantes', 'Curso completo de Python para quem está começando', 1],
            ['Design Gráfico', 'Fundamentos do design gráfico e ferramentas', 2],
            ['Marketing Digital', 'Estratégias de marketing digital para iniciantes', 3]
        ];
        
        foreach ($cursos as $curso) {
            $pdo->prepare("
                INSERT INTO cursos (titulo, descricao, categoria_id, ativo) 
                VALUES (?, ?, ?, TRUE)
            ")->execute($curso);
        }
        echo "✅ Cursos criados: " . count($cursos) . " cursos\n";
        
        // Inserir aulas
        $aulas = [
            ['Introdução ao JavaScript', 'Primeira aula sobre JavaScript e sua história', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1, 30, 1],
            ['Variáveis e Tipos', 'Aprendendo sobre variáveis e tipos de dados em JavaScript', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 2, 45, 1],
            ['Instalando Python', 'Como instalar Python no seu computador', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1, 20, 2],
            ['Primeiro Programa', 'Criando seu primeiro programa em Python', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 2, 35, 2]
        ];
        
        foreach ($aulas as $aula) {
            $pdo->prepare("
                INSERT INTO aulas (titulo, descricao, url_video, ordem, duracao_minutos, curso_id, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, TRUE)
            ")->execute($aula);
        }
        echo "✅ Aulas criadas: " . count($aulas) . " aulas\n";
        
        // Inserir materiais complementares
        $materiais = [
            [1, 'Documentação JavaScript', 'Documentação oficial do JavaScript em português', 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript', 'link', NULL, NULL, 1],
            [1, 'Slides da Aula', 'Slides utilizados na aula de introdução', 'https://exemplo.com/slides.pdf', 'pdf', 2048, 'intro-js-slides.pdf', 2]
        ];
        
        foreach ($materiais as $material) {
            $pdo->prepare("
                INSERT INTO materiais_complementares (aula_id, titulo, descricao, url_arquivo, tipo, tamanho_arquivo, nome_arquivo, ordem, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ")->execute($material);
        }
        echo "✅ Materiais complementares criados: " . count($materiais) . " materiais\n";
        
    } else {
        echo "ℹ️  Dados já existem no banco\n";
    }
    
    echo "\n🎉 Setup do PostgreSQL concluído com sucesso!\n";
    echo "📊 Resumo:\n";
    echo "   • Usuários: " . $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() . "\n";
    echo "   • Categorias: " . $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn() . "\n";
    echo "   • Cursos: " . $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn() . "\n";
    echo "   • Aulas: " . $pdo->query("SELECT COUNT(*) FROM aulas")->fetchColumn() . "\n";
    echo "   • Materiais: " . $pdo->query("SELECT COUNT(*) FROM materiais_complementares")->fetchColumn() . "\n";
    echo "\n👤 Acesso:\n";
    echo "   Email: admin@teste.com\n";
    echo "   Senha: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o setup: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>