<?php
require_once __DIR__ . '/config/database.php';

// Verificar se está configurado para SQLite
if (DB_TYPE !== 'sqlite') {
    die("❌ Este script é apenas para SQLite. Configure DB_TYPE como 'sqlite' no database.php\n");
}

echo "=== SETUP SQLITE - Sistema de Estudos ===\n\n";

try {
    $db = Database::getInstance();
    
    echo "✅ Conexão com SQLite estabelecida\n";
    echo "📁 Arquivo do banco: " . DB_PATH . "\n\n";
    
    echo "🔨 Criando tabelas...\n";
    
    // Tabela usuarios
    $db->execute("
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
    echo "✅ Tabela 'usuarios' criada\n";
    
    // Tabela categorias
    $db->execute("
        CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabela 'categorias' criada\n";
    
    // Tabela cursos
    $db->execute("
        CREATE TABLE IF NOT EXISTS cursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            categoria_id INTEGER NOT NULL,
            imagem_capa TEXT DEFAULT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        )
    ");
    echo "✅ Tabela 'cursos' criada\n";
    
    // Tabela aulas
    $db->execute("
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
    echo "✅ Tabela 'aulas' criada\n";
    
    // Tabela anotacoes
    $db->execute("
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
    echo "✅ Tabela 'anotacoes' criada\n";
    
    // Tabela comentarios
    $db->execute("
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
    echo "✅ Tabela 'comentarios' criada\n";
    
    // Tabela progresso_aulas
    $db->execute("
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
    echo "✅ Tabela 'progresso_aulas' criada\n";
    
    // Tabela materiais_complementares
    $db->execute("
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
    echo "✅ Tabela 'materiais_complementares' criada\n";
    
    echo "\n🔍 Verificando se já existem dados...\n";
    
    // Verificar se já existem dados
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM usuarios")['count'];
    
    if ($userCount == 0) {
        echo "📝 Inserindo dados iniciais...\n";
        
        // Inserir usuário admin
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $db->execute("
            INSERT INTO usuarios (nome, email, senha, is_admin, ativo) 
            VALUES (?, ?, ?, ?, ?)
        ", ['Administrador', 'admin@teste.com', $adminPassword, true, true]);
        echo "✅ Usuário admin criado (admin@teste.com / admin123)\n";
        
        // Inserir categorias
        $categorias = [
            ['Programação', 'Cursos de programação e desenvolvimento'],
            ['Design', 'Cursos de design gráfico e UI/UX'], 
            ['Marketing', 'Cursos de marketing digital e estratégias'],
            ['Negócios', 'Cursos de gestão e empreendedorismo']
        ];
        
        foreach ($categorias as $cat) {
            $db->execute("
                INSERT INTO categorias (nome, descricao, ativo) 
                VALUES (?, ?, ?)
            ", [$cat[0], $cat[1], true]);
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
            $db->execute("
                INSERT INTO cursos (titulo, descricao, categoria_id, ativo) 
                VALUES (?, ?, ?, ?)
            ", [$curso[0], $curso[1], $curso[2], true]);
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
            $db->execute("
                INSERT INTO aulas (titulo, descricao, url_video, ordem, duracao_minutos, curso_id, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$aula[0], $aula[1], $aula[2], $aula[3], $aula[4], $aula[5], true]);
        }
        echo "✅ Aulas criadas: " . count($aulas) . " aulas\n";
        
        // Inserir materiais complementares
        $materiais = [
            [1, 'Documentação JavaScript', 'Documentação oficial do JavaScript em português', 'https://developer.mozilla.org/pt-BR/docs/Web/JavaScript', 'link', NULL, NULL, 1],
            [1, 'Slides da Aula', 'Slides utilizados na aula de introdução', 'https://exemplo.com/slides.pdf', 'pdf', 2048, 'intro-js-slides.pdf', 2]
        ];
        
        foreach ($materiais as $material) {
            $db->execute("
                INSERT INTO materiais_complementares (aula_id, titulo, descricao, url_arquivo, tipo, tamanho_arquivo, nome_arquivo, ordem, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$material[0], $material[1], $material[2], $material[3], $material[4], $material[5], $material[6], $material[7], true]);
        }
        echo "✅ Materiais complementares criados: " . count($materiais) . " materiais\n";
        
    } else {
        echo "ℹ️  Dados já existem no banco\n";
    }
    
    echo "\n🎉 Setup do SQLite concluído com sucesso!\n";
    echo "📊 Resumo:\n";
    echo "   • Usuários: " . $db->fetchOne("SELECT COUNT(*) as count FROM usuarios")['count'] . "\n";
    echo "   • Categorias: " . $db->fetchOne("SELECT COUNT(*) as count FROM categorias")['count'] . "\n";
    echo "   • Cursos: " . $db->fetchOne("SELECT COUNT(*) as count FROM cursos")['count'] . "\n";
    echo "   • Aulas: " . $db->fetchOne("SELECT COUNT(*) as count FROM aulas")['count'] . "\n";
    echo "   • Materiais: " . $db->fetchOne("SELECT COUNT(*) as count FROM materiais_complementares")['count'] . "\n";
    echo "\n👤 Acesso:\n";
    echo "   Email: admin@teste.com\n";
    echo "   Senha: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o setup: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>