# 📚 Sistema LMS - Plataforma de Ensino Online

Sistema de gerenciamento de aprendizagem (LMS) completo, desenvolvido em PHP com suporte a PostgreSQL e SQLite.

## 🚀 Funcionalidades

### 👨‍🎓 Para Estudantes
- ✅ Visualização de cursos e aulas em vídeo
- ✅ Acompanhamento de progresso
- ✅ Sistema de anotações por aula
- ✅ Marcação de aulas como concluídas
- ✅ Modo teatro para visualização imersiva
- ✅ Navegação intuitiva entre aulas
- ✅ Dashboard com estatísticas de progresso

### 🎓 Recursos de Aulas
- 📹 Suporte a vídeos do Google Drive e outras plataformas
- 📝 Descrições detalhadas
- 📎 Materiais complementares para download
- ⏱️ Duração estimada de cada aula
- 🎯 Indicadores visuais de conclusão

### 💡 Interface e UX
- 🎨 Design moderno com Tailwind CSS
- 📱 Totalmente responsivo
- 🌙 Modo teatro com overlay escurecido
- ✨ Animações suaves
- 🎯 Scroll automático para aula atual
- 🟢 Indicadores visuais de progresso (barras verdes)

## 🛠️ Tecnologias

- **Backend**: PHP 7.4+
- **Banco de Dados**: PostgreSQL 12+ / SQLite 3
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Frameworks CSS**: Tailwind CSS
- **Ícones**: Font Awesome 6

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- PostgreSQL 12+ (para produção) ou SQLite 3 (para desenvolvimento)
- Extensões PHP:
  - PDO
  - pdo_pgsql (para PostgreSQL)
  - pdo_sqlite (para SQLite)
  - mbstring
  - json

## 🔧 Instalação

### 1. Clone o repositório
```bash
git clone <seu-repositorio>
cd <nome-do-projeto>
```

### 2. Configure o banco de dados

#### Opção A: PostgreSQL (Produção)
```bash
# Copie o arquivo de configuração de exemplo
cp config/database.example.php config/database.php

# Edite o arquivo com suas credenciais
nano config/database.php

# Execute o script de setup
php setup_postgres.php
```

#### Opção B: SQLite (Desenvolvimento)
```bash
# Execute o script de setup
php setup_sqlite.php
```

### 3. Configure permissões
```bash
chmod 755 config/
chmod 644 config/*.php
chmod 666 config/estudos.db  # Se usar SQLite
```

### 4. Acesse o sistema
- Abra seu navegador em `http://localhost/`
- Use as credenciais padrão ou crie um novo usuário

## 📊 Estrutura do Projeto

```
.
├── assets/               # Arquivos estáticos (CSS, JS, imagens)
│   ├── css/
│   ├── js/
│   └── images/
├── config/              # Configurações do sistema
│   ├── database.php     # Configuração do banco de dados
│   └── estudos.db       # Banco SQLite (se aplicável)
├── includes/            # Arquivos PHP incluídos
│   ├── auth.php         # Autenticação e funções auxiliares
│   └── header.php       # Header comum
├── uploads/             # Arquivos enviados pelos usuários
├── aula.php             # Página de visualização de aulas
├── curso.php            # Página de detalhes do curso
├── home.php             # Dashboard do estudante
├── login.php            # Página de login
├── logout.php           # Logout
├── sync_database.php    # Script de sincronização entre bancos
├── setup_postgres.php   # Setup PostgreSQL
├── setup_sqlite.php     # Setup SQLite
└── README.md            # Este arquivo
```

## 🔄 Sincronização de Dados

O projeto inclui um script para sincronizar dados entre PostgreSQL e SQLite:

```bash
# PostgreSQL → SQLite
php sync_database.php --from=postgresql --to=sqlite

# SQLite → PostgreSQL
php sync_database.php --from=sqlite --to=postgresql

# Com truncate (limpa destino antes)
php sync_database.php --from=postgresql --to=sqlite --truncate

# Sincronizar apenas tabelas específicas
php sync_database.php --from=postgresql --to=sqlite --tables=usuarios,cursos,aulas
```

## 📚 Banco de Dados

### Tabelas Principais

- **usuarios**: Gerenciamento de usuários
- **categorias**: Categorias de cursos
- **cursos**: Cursos disponíveis
- **aulas**: Aulas de cada curso
- **materiais_complementares**: Materiais de apoio
- **anotacoes**: Anotações dos estudantes
- **progresso_aulas**: Tracking de progresso
- **progresso_cursos**: Estatísticas de progresso

## 🎨 Características da Interface

### Modo Teatro
- Tela escurecida com overlay
- Vídeo centralizado e ampliado
- 3 formas de sair:
  - Tecla `ESC`
  - Clique no botão
  - Clique fora do vídeo

### Lista de Aulas
- ✅ Barra verde (4px) para aulas concluídas
- 🔵 Destaque azul para aula atual
- 📜 Scroll automático para aula em reprodução
- ⏱️ Duração real de cada aula
- ✓ Ícone de check para aulas finalizadas

### Navegação
- Botões "Anterior" e "Próxima"
- Breadcrumb de navegação
- Links rápidos entre aulas

## 🔐 Segurança

- ✅ Senhas hashadas com `password_hash()`
- ✅ Proteção contra SQL Injection (prepared statements)
- ✅ Sanitização de HTML com `htmlspecialchars()`
- ✅ Validação de sessões
- ✅ Controle de acesso por páginas

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 👨‍💻 Autor

Desenvolvido com ❤️ para facilitar o aprendizado online.

## 📞 Suporte

Para suporte, abra uma issue no repositório ou entre em contato através do email de suporte.

---

⭐ Se este projeto foi útil para você, considere dar uma estrela no repositório!
