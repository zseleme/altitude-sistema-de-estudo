# Instalação Automática do Sistema

## Como Funciona

O sistema agora possui **instalação automática** do banco de dados. Quando você acessa o sistema pela primeira vez, ele:

1. **Detecta** que o banco de dados não existe
2. **Cria automaticamente** o arquivo `config/estudos.db` (SQLite)
3. **Cria todas as tabelas** necessárias (usuarios, categorias, cursos, aulas, simulados, etc.)
4. **Insere dados iniciais**:
   - Usuário administrador padrão
   - Categorias de exemplo
   - Cursos de exemplo

## Primeiro Acesso

### Credenciais Padrão

Após a instalação automática, use estas credenciais para fazer login:

- **Email:** `admin@teste.com`
- **Senha:** `admin123`

⚠️ **IMPORTANTE:** Altere a senha padrão após o primeiro acesso!

## Estrutura Criada

O sistema cria automaticamente as seguintes tabelas:

### Tabelas Principais
- `usuarios` - Usuários do sistema
- `categorias` - Categorias de cursos
- `cursos` - Cursos disponíveis
- `aulas` - Aulas dos cursos

### Tabelas de Interação
- `anotacoes` - Anotações dos usuários nas aulas
- `comentarios` - Comentários nas aulas
- `progresso_aulas` - Progresso dos usuários
- `materiais_complementares` - Materiais extras

### Tabelas de Simulados
- `simulados` - Simulados/provas
- `simulado_questoes` - Questões dos simulados
- `simulado_respostas` - Respostas dos usuários
- `simulado_tentativas` - Tentativas dos usuários

### Tabelas de Inglês
- `english_diary` - Diário de inglês
- `english_notes` - Anotações de inglês

### Outras Tabelas
- `certificados` - Certificados emitidos
- `ai_settings` - Configurações de IA

## Migração para PostgreSQL

Se você quiser usar PostgreSQL em vez de SQLite:

### Passo 1: Acesse as Configurações

1. Faça login como administrador
2. Vá em **Administração → Configurações do Banco**

### Passo 2: Configure o PostgreSQL

1. Preencha os dados de conexão:
   - Host (ex: `localhost`)
   - Porta (padrão: `5432`)
   - Nome do Database (ex: `estudos_db`)
   - Schema (padrão: `estudos`)
   - Usuário (ex: `postgres`)
   - Senha

2. Clique em **"Salvar e Migrar para PostgreSQL"**

### Passo 3: Execute as Migrações

Após salvar, você precisa criar as tabelas no PostgreSQL:

```bash
# Acesse a pasta do projeto
cd /caminho/do/projeto

# Execute as migrações
php migrations/add_simulados_table.php
php migrations/add_english_section.php
php migrations/add_certificates_table.php
php migrations/add_ai_settings_table.php
php migrations/add_cursos_arquivados_table.php
php migrations/add_youtube_api_key.php
```

### Passo 4: Migre os Dados (Opcional)

Se você já tem dados no SQLite e quer migrar para PostgreSQL, use o script de setup:

```bash
php setup_postgres.php
```

### Passo 5: Faça Logout

Faça logout e login novamente para que o sistema use o PostgreSQL.

## Voltando para SQLite

Se quiser voltar para SQLite:

1. Acesse **Administração → Configurações do Banco**
2. Clique em **"Voltar para SQLite"**
3. Faça logout e login novamente

## Arquivos Importantes

### Auto-Instalação
- `includes/auto_install.php` - Script que detecta e cria o banco automaticamente
- `includes/auth.php` - Inclui o auto_install.php no início

### Configurações
- `config/database.php` - Arquivo de configuração do banco (criado automaticamente)
- `config/database.example.php` - Exemplo de configuração
- `config/estudos.db` - Banco SQLite (criado automaticamente)

### Administração
- `admin/configuracoes_banco.php` - Página para configurar PostgreSQL

## Requisitos

### Para SQLite (Padrão)
- PHP 7.4 ou superior
- Extensão PDO_SQLite habilitada
- Permissões de escrita na pasta `config/`

### Para PostgreSQL (Opcional)
- PostgreSQL 12 ou superior instalado e rodando
- Extensão PDO_PGSQL habilitada no PHP
- Database criado no PostgreSQL
- Usuário com permissões adequadas

## Solução de Problemas

### Erro: "Não foi possível criar o banco de dados"

**Causa:** Falta de permissões de escrita

**Solução:**
```bash
# Linux/Mac
chmod 755 config/
chmod 644 config/*

# Ou dar permissões totais (não recomendado em produção)
chmod 777 config/
```

### Erro: "Erro de conexão com PostgreSQL"

**Causas possíveis:**
1. PostgreSQL não está rodando
2. Credenciais incorretas
3. Database não existe
4. Firewall bloqueando conexão

**Soluções:**
```bash
# Verificar se PostgreSQL está rodando
sudo systemctl status postgresql

# Criar database
sudo -u postgres psql
CREATE DATABASE estudos_db;
CREATE SCHEMA estudos;
\q

# Testar conexão
psql -h localhost -U postgres -d estudos_db
```

### Tabelas não aparecem no PostgreSQL

**Causa:** Migrações não foram executadas

**Solução:** Execute todas as migrações manualmente:
```bash
php migrations/add_simulados_table.php
php migrations/add_english_section.php
php migrations/add_certificates_table.php
# ... e assim por diante
```

## Segurança

### Produção

Em ambiente de produção:

1. **Altere a senha do admin** imediatamente
2. **Use PostgreSQL** em vez de SQLite
3. **Configure backups** regulares
4. **Remova** ou proteja arquivos sensíveis:
   - `setup_postgres.php`
   - `setup_sqlite.php`
   - `migrations/` (após executar)

### Permissões de Arquivos

```bash
# Permissões recomendadas
chmod 644 config/database.php
chmod 600 config/estudos.db  # Somente leitura/escrita pelo owner
chmod 644 includes/*.php
chmod 644 admin/*.php
```

## Desenvolvimento Local

Para desenvolvimento, o SQLite é suficiente e não requer configuração adicional. O sistema funciona imediatamente após clonar o repositório.

## Backup

### SQLite
```bash
# Fazer backup
cp config/estudos.db config/estudos.db.backup

# Restaurar backup
cp config/estudos.db.backup config/estudos.db
```

### PostgreSQL
```bash
# Fazer backup
pg_dump -U postgres -d estudos_db > backup.sql

# Restaurar backup
psql -U postgres -d estudos_db < backup.sql
```

## Suporte

Se encontrar problemas:

1. Verifique os logs do PHP
2. Verifique permissões de arquivo
3. Certifique-se de que as extensões PDO estão habilitadas
4. Para PostgreSQL, verifique se o serviço está rodando

## Resumo

O sistema agora é **plug and play**:

1. Clone o repositório
2. Acesse no navegador
3. O banco é criado automaticamente
4. Faça login com `admin@teste.com` / `admin123`
5. Pronto para usar!

Opcionalmente, você pode migrar para PostgreSQL quando quiser através da interface de administração.
