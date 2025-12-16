# 📚 Altitude Sistema de Estudo

Sistema de gerenciamento de aprendizagem (LMS) completo desenvolvido em PHP vanilla, com suporte a PostgreSQL e SQLite, integração com IA para análise de questões, e ferramentas avançadas de estudo.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-PostgreSQL%20%7C%20SQLite-green.svg)](https://www.postgresql.org/)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## 🚀 Principais Funcionalidades

### 👨‍🎓 Para Estudantes

- **Gerenciamento de Cursos**
  - Visualização de cursos por categorias
  - Aulas em vídeo (Google Drive, YouTube, etc.)
  - Sistema de progresso com tracking automático
  - Dashboard com estatísticas detalhadas
  - Certificados de conclusão

- **Sistema de Simulados**
  - Criação e resolução de simulados/provas
  - Questões de múltipla escolha (A/B/C/D/E)
  - Análise inteligente de respostas erradas com IA
  - Histórico de tentativas e desempenho
  - Timer e pontuação automática

- **Ferramentas de Estudo**
  - Anotações por aula
  - Materiais complementares para download
  - Modo teatro para visualização imersiva
  - Estudo de inglês com flashcards
  - Navegação intuitiva entre conteúdos

### 🎯 Para Administradores

- **Gestão de Conteúdo**
  - CRUD completo de cursos, aulas e categorias
  - Upload de materiais complementares
  - Importação de playlists do YouTube
  - Geração de certificados personalizados

- **Sistema de Simulados**
  - Criação e edição de simulados
  - Gerenciamento de questões
  - Análise de desempenho dos alunos

- **Integração com IA**
  - Configuração de múltiplos provedores (OpenAI, Google Gemini, Groq)
  - Análise automática de respostas erradas
  - Prompts personalizáveis
  - Gerenciamento de tokens e custos

## 🤖 Integração com IA

O sistema oferece análise inteligente de questões através de múltiplos provedores de IA:

- **OpenAI GPT-4o-mini**: Análise avançada e precisa
- **Google Gemini 2.5 Flash**: Rápido e eficiente (padrão)
- **Groq Llama 3.1**: Alta velocidade de inferência

A IA fornece:
- Explicação detalhada do erro cometido
- Análise da resposta correta
- Dicas para evitar erros similares
- Conteúdo relacionado para estudo

## 🛠️ Tecnologias

### Backend
- **PHP 7.4+**: Vanilla PHP, sem frameworks
- **PostgreSQL 12+** / **SQLite 3**: Suporte dual-database
- **PDO**: Prepared statements para segurança
- **API REST**: Endpoints JSON para AJAX

### Frontend
- **Tailwind CSS**: Estilização moderna e responsiva
- **JavaScript ES6+**: Interatividade nativa
- **Font Awesome 6**: Biblioteca de ícones
- **Responsive Design**: Mobile-first

### DevOps
- **GitHub Actions**: CI/CD automatizado
- **FTP Deploy**: Deploy automático para produção/staging
- **Version Tracking**: Controle de versões via Git

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- PostgreSQL 12+ (produção) ou SQLite 3 (desenvolvimento)
- Extensões PHP necessárias:
  - `pdo`
  - `pdo_pgsql` (para PostgreSQL)
  - `pdo_sqlite` (para SQLite)
  - `mbstring`
  - `json`
  - `curl`

## 🔧 Instalação

### Instalação Automática (Recomendado)

O sistema possui **auto-instalação inteligente**. Basta acessar a aplicação:

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/altitude-sistema-de-estudo.git
cd altitude-sistema-de-estudo
```

2. Inicie o servidor PHP:
```bash
php -S localhost:8000
```

3. Acesse `http://localhost:8000` no navegador

O sistema irá automaticamente:
- Criar o arquivo de configuração do banco de dados
- Criar o banco SQLite com todas as tabelas
- Inserir dados iniciais (admin, categorias, configurações)

### Credenciais Padrão

Após a instalação automática:
- **Email**: `admin@teste.com`
- **Senha**: `admin123`

**⚠️ Importante**: Altere as credenciais padrão após o primeiro acesso!

### Instalação Manual (PostgreSQL)

Se preferir usar PostgreSQL:

1. Copie o arquivo de configuração:
```bash
cp config/database.example.php config/database.php
```

2. Edite `config/database.php` com suas credenciais PostgreSQL

3. Execute o script de setup:
```bash
php setup_postgres.php
```

## 📊 Estrutura do Projeto

```
altitude-sistema-de-estudo/
├── admin/                      # Área administrativa
│   ├── categorias.php          # Gestão de categorias
│   ├── cursos.php              # Gestão de cursos
│   ├── aulas.php               # Gestão de aulas
│   ├── simulados.php           # Gestão de simulados
│   ├── configuracoes_ia.php    # Configurações de IA
│   └── ...
├── api/                        # Endpoints REST
│   ├── simulados.php           # API de simulados
│   ├── analise_questao_ia.php  # Análise com IA
│   ├── progresso.php           # Tracking de progresso
│   ├── anotacoes.php           # Sistema de notas
│   └── ...
├── assets/                     # Arquivos estáticos
│   ├── css/                    # Estilos personalizados
│   ├── js/                     # Scripts JavaScript
│   └── images/                 # Imagens e ícones
├── config/                     # Configurações
│   ├── database.php            # Config do banco (auto-gerado)
│   ├── database.example.php    # Template de config
│   └── estudos.db              # Banco SQLite (auto-criado)
├── includes/                   # Bibliotecas PHP
│   ├── auth.php                # Autenticação
│   ├── auto_install.php        # Sistema de auto-instalação
│   ├── ai_helper.php           # Helper de IA
│   ├── layout.php              # Sistema de layout
│   └── version.php             # Controle de versão
├── ingles/                     # Sistema de inglês
│   ├── flashcards.php          # Flashcards
│   └── ...
├── uploads/                    # Arquivos enviados
│   └── certificados/           # Certificados gerados
├── .github/workflows/          # GitHub Actions
│   └── ftp-deploy.yml          # Deploy automatizado
├── aula.php                    # Visualização de aulas
├── curso.php                   # Detalhes do curso
├── home.php                    # Dashboard
├── simulado.php                # Interface de simulados
├── CLAUDE.md                   # Instruções para Claude Code
└── README.md                   # Este arquivo
```

## 🗄️ Arquitetura do Banco de Dados

### Camada de Abstração

O sistema utiliza uma **camada de abstração customizada** em `config/database.php` que garante compatibilidade entre PostgreSQL e SQLite:

```php
$db = Database::getInstance();

// Detectar tipo de banco
if ($db->isSQLite()) {
    // SQL específico para SQLite
} else {
    // SQL específico para PostgreSQL
}

// Helpers de compatibilidade
$db->getBoolTrue();   // TRUE ou 1
$db->getBoolFalse();  // FALSE ou 0
```

### Principais Tabelas

- **usuarios**: Usuários do sistema
- **categorias**: Categorias de cursos
- **cursos**: Cursos disponíveis
- **aulas**: Aulas de cada curso
- **materiais_complementares**: Materiais de apoio
- **simulados**: Simulados/provas
- **simulado_questoes**: Questões dos simulados
- **simulado_tentativas**: Tentativas dos alunos
- **simulado_respostas**: Respostas (com análise IA)
- **progresso_aulas**: Progresso por aula
- **progresso_cursos**: Progresso por curso
- **anotacoes**: Anotações dos estudantes
- **configuracoes**: Configurações do sistema (incluindo IA)
- **ingles_***: Tabelas do sistema de inglês

**Nota**: Todo o schema está definido em `includes/auto_install.php` - não há sistema de migrations separado.

## 🎨 Interface e UX

### Design Responsivo
- Layout adaptativo para desktop, tablet e mobile
- Sidebar retrátil em dispositivos móveis
- Grid system com Tailwind CSS
- Componentes reutilizáveis

### Modo Teatro
- Visualização imersiva de vídeos
- Overlay escurecido
- Controles de navegação
- Saída via ESC, clique fora ou botão

### Indicadores Visuais
- Barras de progresso verdes
- Badges de conclusão
- Scroll automático para conteúdo atual
- Feedback visual em ações

## 🔐 Segurança

- ✅ **Autenticação**: Sistema de sessões seguro
- ✅ **Senhas**: Hash com `password_hash()` (bcrypt)
- ✅ **SQL Injection**: Prepared statements (PDO)
- ✅ **XSS**: Sanitização com `htmlspecialchars()`
- ✅ **CSRF**: Validação de origem de requisições
- ✅ **Controle de Acesso**: `requireLogin()` e `requireAdmin()`

## 🚀 Deploy

### Deploy Automatizado (GitHub Actions)

O projeto possui deploy **totalmente automatizado**:

- **Push para `main`** → Deploy em **produção** (seleme.pt)
- **Push para `develop`** → Deploy em **staging** (dev.seleme.pt)

O workflow (`ftp-deploy.yml`) executa:
1. Validação de sintaxe PHP
2. Geração de `version.json`
3. Deploy via FTP
4. Exclusão de arquivos desnecessários

### Deploy Manual

```bash
# 1. Validar sintaxe PHP
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# 2. Fazer upload via FTP/SFTP
# Excluir: .git, .github, node_modules, .env, config/database.php

# 3. Configurar permissões no servidor
chmod 755 config/
chmod 666 config/estudos.db  # Se usar SQLite
```

## 📈 Sistema de Versões

O sistema detecta automaticamente o ambiente:

- **Produção**: Arquivo `version.json` com env=Produção
- **Desenvolvimento**: Arquivo `version.json` com env=Desenvolvimento
- **Local**: Presença de pasta `.git/`

Versão exibida no rodapé da sidebar com código de cores:
- 🟢 Verde: Produção
- 🟡 Amarelo: Desenvolvimento
- ⚪ Cinza: Local

## 🧪 Desenvolvimento

### Servidor Local

```bash
# Iniciar servidor PHP
php -S localhost:8000

# Acessar aplicação
http://localhost:8000
```

### Boas Práticas

1. **Compatibilidade SQL**: Sempre teste queries em ambos os bancos
2. **Prepared Statements**: Use sempre para queries dinâmicas
3. **Layout System**: Use `renderLayout()` para páginas padrão
4. **API Pattern**: Siga o padrão switch/case em `api/`
5. **Sem Over-engineering**: Mantenha simplicidade

### Exemplo de Query Compatível

```php
// ✅ Correto - Compatível com ambos
$isTrue = $db->isSQLite() ? 1 : 'TRUE';
$query = "SELECT * FROM table WHERE active = $isTrue";

// ❌ Errado - MySQL-specific
$query = "INSERT ... ON DUPLICATE KEY UPDATE ...";

// ✅ Correto - Padrão manual
$existing = $db->fetchOne("SELECT id FROM table WHERE key = ?", [$key]);
if ($existing) {
    $db->execute("UPDATE table SET value = ?", [$value]);
} else {
    $db->execute("INSERT INTO table (key, value) VALUES (?, ?)", [$key, $value]);
}
```

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/MinhaFeature`
3. Commit: `git commit -m 'feat: adiciona MinhaFeature'`
4. Push: `git push origin feature/MinhaFeature`
5. Abra um Pull Request para `develop`

### Convenções

- **Commits**: Siga [Conventional Commits](https://www.conventionalcommits.org/)
- **Código**: PSR-12 para PHP, ESLint para JavaScript
- **Branches**: `feature/`, `fix/`, `docs/`, etc.
- **PRs**: Sempre para `develop`, nunca direto para `main`

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👨‍💻 Autores

Desenvolvido com dedicação para democratizar o acesso à educação de qualidade.

## 🐛 Suporte

- **Issues**: [GitHub Issues](https://github.com/seu-usuario/altitude-sistema-de-estudo/issues)
- **Documentação**: Veja [CLAUDE.md](CLAUDE.md) para detalhes técnicos
- **Email**: suporte@seleme.pt

## 🌟 Agradecimentos

- [Tailwind CSS](https://tailwindcss.com/) - Framework CSS
- [Font Awesome](https://fontawesome.com/) - Ícones
- [OpenAI](https://openai.com/), [Google Gemini](https://deepmind.google/technologies/gemini/), [Groq](https://groq.com/) - Provedores de IA

---

⭐ Se este projeto foi útil para você, considere dar uma estrela no repositório!

**Desenvolvido com ❤️ pela equipe Altitude**
