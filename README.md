# 📚 Altitude Sistema de Estudo

Sistema de gerenciamento de aprendizagem (LMS) completo desenvolvido em PHP vanilla, com suporte a PostgreSQL e SQLite, integração com IA para análise de questões e geração de conteúdo, e ferramentas avançadas de estudo.

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

- **Ferramentas de Estudo de Inglês**
  - **Lições com IA**: Geração de lições personalizadas sobre qualquer tema (Múltipla escolha, Lacunas e Escrita)
  - **Diário de Estudos**: Prática diária de escrita com revisão gramatical por IA
  - **Revisão Expert**: Feedback detalhado e encorajador gerado por IA
  - **Exportação**: Sistema para exportar frases e progressos

### 🎯 Para Administradores

- **Gestão de Conteúdo**
  - CRUD completo de cursos, aulas e categorias
  - Upload de materiais complementares
  - **Integração YouTube**: Importação automática de playlists via YouTube Data API v3
  - Geração de certificados personalizados

- **Sistema de Simulados**
  - Criação e edição de simulados
  - Gerenciamento de questões
  - Análise de desempenho dos alunos

- **Integração com IA**
  - Configuração de múltiplos provedores (OpenAI, Google Gemini, Groq)
  - Gerenciamento centralizado de modelos e temperaturas
  - Prompts personalizáveis para diferentes contextos
  - **Segurança**: Chaves de API criptografadas em repouso (AES-256)

## 🤖 Integração com IA

O sistema oferece análise inteligente de questões e geração de lições através de múltiplos provedores:

- **Google Gemini 2.5 Flash**: Rápido e eficiente (Padrão recomendado)
- **OpenAI GPT-4o-mini**: Qualidade superior e análise precisa
- **Groq Llama 3.1 8b**: Altíssima velocidade de inferência

A IA fornece:
- Explicação detalhada de erros em simulados
- Geração de questões gramaticais e de escrita em inglês
- Revisão pedagógica de textos livres
- Dicas de estudo personalizadas

## 🛠️ Tecnologias

### Backend
- **PHP 7.4+**: Vanilla PHP, focado em performance e simplicidade
- **PostgreSQL 12+** / **SQLite 3**: Suporte dual-database com camada de abstração
- **PDO**: Prepared statements para proteção total contra SQL Injection
- **API REST**: Endpoints JSON para comunicação assíncrona (AJAX)

### Frontend
- **Tailwind CSS**: Estilização moderna, responsiva e customizada
- **JavaScript ES6+**: Interatividade nativa sem dependências pesadas
- **Font Awesome 6**: Biblioteca completa de ícones
- **Responsive Design**: Experiência otimizada para Desktop e Mobile

### DevOps & Infra
- **GitHub Actions**: CI/CD para validação e deploy automatizado
- **FTP Deploy**: Sincronização automática para servidores de produção/staging
- **Nixpacks**: Suporte nativo para plataformas como Railway e Render
- **Version Tracking**: Controle rigoroso de versões e ambientes

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- PostgreSQL 12+ (produção) ou SQLite 3 (desenvolvimento)
- Extensões PHP obrigatórias: `pdo`, `pdo_pgsql`/`pdo_sqlite`, `mbstring`, `json`, `curl`, `openssl`

## 🔧 Instalação

### Instalação Automática (Recomendado)

O sistema possui **auto-instalação inteligente**. Basta acessar a aplicação pela primeira vez:

1. Clone o repositório:
```bash
git clone https://github.com/zseleme/altitude-sistema-de-estudo.git
cd altitude-sistema-de-estudo
```

2. Inicie o servidor PHP:
```bash
php -S localhost:8000
```

3. Acesse `http://localhost:8000` no navegador.

O sistema irá automaticamente:
- Criar o arquivo de configuração `config/database.php`
- Inicializar o banco de dados (SQLite por padrão)
- Criar todas as tabelas e dados mestres (admin, categorias, configurações de IA)

### Credenciais Padrão
- **Email**: `admin@teste.com`
- **Senha**: `admin123`
*(Altere imediatamente após o primeiro login em /alterar_senha.php)*

## 📊 Estrutura do Projeto

```
altitude-sistema-de-estudo/
├── admin/                      # Painel administrativo
│   ├── configuracoes_ia.php    # Gestão de APIs e Provedores
│   ├── cursos.php              # Gestão de LMS
│   └── ...
├── api/                        # Endpoints REST JSON
│   ├── analise_questao_ia.php  # Motor de análise de questões
│   ├── importar_playlist_yt.php# Integração com YouTube API
│   ├── ingles_licoes.php       # Controller de lições com IA
│   └── ...
├── config/                     # Configurações e Banco Local
│   ├── database.php            # Abstração de banco (auto-gerado)
│   └── estudos.db              # Banco SQLite padrão
├── includes/                   # Núcleo do sistema
│   ├── ai_helper.php           # Abstração de Provedores IA
│   ├── auto_install.php        # Engine de auto-instalação e Schema
│   ├── encryption_helper.php   # Segurança (AES-256-CBC)
│   └── layout.php              # Sistema de templates
├── ingles/                     # Módulo de idiomas
│   ├── diario.php              # Diário de escrita
│   ├── licoes.php              # Gerador de lições com IA
│   └── ...
├── .github/workflows/          # Automação de Deploy
├── index.php                   # Portal do aluno
└── README.md                   # Documentação
```

## 🎨 Interface e UX

O sistema foi desenhado para proporcionar uma experiência de estudo imersiva e moderna:

- **Design Responsivo**: Interface adaptativa construída com Tailwind CSS, garantindo produtividade no desktop e mobilidade no celular.
- **Gráficos e Indicadores**: Visualização Clara do progresso com barras dinâmicas, badges de conquista e estatísticas de desempenho.
- **Modo Estudo**: Interface limpa e sem distrações durante as aulas, com suporte a anotações em tempo real.
- **Interatividade**: Feedback visual instantâneo em simulados e lições, com explicações contextuais geradas por IA.

## 🔐 Segurança e Criptografia

O Altitude prioriza a segurança dos seus dados e credenciais:

### Criptografia de Chaves de API
Todas as chaves sensíveis (OpenAI, Gemini, Groq, YouTube) são armazenadas criptografadas no banco de dados utilizando **AES-256-CBC**.

Para ativar esta funcionalidade, você **deve** configurar uma chave mestra:

**1. Gerar uma chave forte:**
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

**2. Configurar no Ambiente (Produção):**
Defina a variável de ambiente `ENCRYPTION_KEY` no seu servidor ou `.htaccess`:
```apache
SetEnv ENCRYPTION_KEY "sua-chave-gerada-aqui"
```

*Nota: Em ambiente de desenvolvimento local (detectado pela pasta .git), o sistema gera automaticamente uma chave em `config/encryption.key` se não encontrar a variável de ambiente.*

## 🚀 Deploy

### Deploy Automatizado
O projeto está configurado com GitHub Actions para deploy contínuo:
- **Branch `main`**: Deploy para Produção
- **Branch `develop`**: Deploy para Staging

### Deploy via Nixpacks
Compatível com plataformas modernas:
```bash
nixpacks build .
```

## 🧪 Desenvolvimento

1. **Compatibilidade**: Sempre use o helper `$db->isSQLite()` ou `$db->isPostgreSQL()` para queries específicas.
2. **Boas Práticas**: Todas as novas funcionalidades de IA devem passar pelo `AIHelper`.
3. **Schema**: Mudanças no banco de dados devem ser refletidas em `includes/auto_install.php`.

---

⭐ Se este projeto é útil para você, considere dar uma estrela no repositório!

**Desenvolvido com ❤️ pela equipe Altitude**
