# GitHub Actions - Altitude Sistema de Estudos

Este diretório contém as workflows (actions) automatizadas do projeto.

## 📋 Workflows Disponíveis

### 1. CI - Verificação de Código (`ci.yml`)

**Quando executa:** A cada push ou pull request nas branches main, develop e feature/*

**O que faz:**
- ✅ Verifica sintaxe PHP em múltiplas versões (7.4, 8.0, 8.1, 8.2)
- ✅ Valida estrutura de pastas obrigatórias
- ✅ Verifica arquivos importantes
- ✅ Testa o sistema de auto-instalação
- ✅ Verifica criação de tabelas no SQLite
- ✅ Valida criação do usuário admin
- ✅ Busca por vulnerabilidades de segurança
- ✅ Detecta credenciais hardcoded

**Status:** ✅ Ativo

---

### 2. Validação de Pull Request (`pr-validation.yml`)

**Quando executa:** Quando um PR é criado, editado ou atualizado

**O que faz:**
- 📊 Valida título do PR (mínimo 10 caracteres)
- 📈 Analisa tamanho do PR (arquivos e linhas)
- 🔍 Detecta arquivos sensíveis modificados
- 📝 Analisa qualidade das mensagens de commit
- 🏷️ Adiciona labels automáticas:
  - `area: admin`, `area: api`, `area: core`, `area: database`
  - `documentation`, `ci/cd`
  - `size: small/medium/large`
- 💬 Comenta estatísticas no PR automaticamente

**Status:** ✅ Ativo

---

### 3. Deploy Automático (`deploy.yml`)

**Quando executa:** A cada push na branch main (ou manualmente)

**O que faz:**
- 🔍 Valida sintaxe PHP antes do deploy
- 📦 Cria arquivo de versão e build info
- 📤 Upload de artefatos (excluindo arquivos sensíveis)
- 🚀 Deploy via FTP (quando configurado)
- 🔐 Deploy via SSH (quando configurado)
- 📢 Notificações de deploy
- 🏷️ Cria tags de versão automáticas
- 🎉 Gera releases no GitHub
- 🏥 Health checks pós-deploy

**Status:** ⚠️ Parcialmente configurado (requer secrets)

**Como habilitar:**
1. Configure os secrets no GitHub:
   - `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (para deploy FTP)
   - `SSH_HOST`, `SSH_USERNAME`, `SSH_PRIVATE_KEY` (para deploy SSH)
2. Descomente as seções relevantes no arquivo `deploy.yml`

---

### 4. Verificar Migrações de Banco (`database-migrations.yml`)

**Quando executa:** Quando há mudanças na pasta `migrations/`

**O que faz:**
- 🗄️ Lista todas as migrações
- ✅ Valida sintaxe das migrações
- 🧪 Testa migrações em banco SQLite temporário
- 📊 Verifica estrutura do banco após migrações
- 🔗 Testa integridade referencial (Foreign Keys)
- 📝 Verifica nomenclatura das migrações
- 💬 Comenta no PR com instruções de execução
- 🐘 Testa compatibilidade com PostgreSQL

**Status:** ✅ Ativo

---

## 🔧 Configuração

### Secrets Necessários

Para habilitar todas as funcionalidades, configure estes secrets no GitHub:

#### Deploy FTP
```
FTP_SERVER=ftp.seu-servidor.com
FTP_USERNAME=seu-usuario
FTP_PASSWORD=sua-senha
```

#### Deploy SSH
```
SSH_HOST=seu-servidor.com
SSH_USERNAME=seu-usuario
SSH_PRIVATE_KEY=sua-chave-privada
SSH_PORT=22  # opcional
```

#### Notificações (opcional)
```
DISCORD_WEBHOOK=https://discord.com/api/webhooks/...
SLACK_WEBHOOK=https://hooks.slack.com/services/...
```

### Como Adicionar Secrets

1. Vá em **Settings** → **Secrets and variables** → **Actions**
2. Clique em **New repository secret**
3. Adicione cada secret conforme necessário

---

## 📊 Status das Actions

Você pode ver o status das actions em:
- **Actions tab** no GitHub
- **Badge** no README principal (se configurado)
- **Checks** em cada Pull Request

---

## 🎯 Triggers (Quando as Actions Executam)

### Eventos que Disparam Actions

| Evento | Workflows Afetados |
|--------|-------------------|
| Push na `main` | CI, Deploy, Database Migrations |
| Push em `feature/*` | CI |
| Pull Request aberto/editado | CI, PR Validation, Database Migrations |
| Mudança em `migrations/` | Database Migrations |
| Execução manual | Deploy |

---

## 🚀 Como Usar

### Executar Action Manualmente

1. Vá em **Actions**
2. Selecione o workflow (ex: "Deploy Automático")
3. Clique em **Run workflow**
4. Selecione a branch
5. Clique em **Run workflow**

### Ver Resultados

1. Vá em **Actions**
2. Clique no workflow execution
3. Veja os logs de cada job
4. Expanda steps para ver detalhes

### Debugar Falhas

Se uma action falhar:

1. Clique no workflow com ❌
2. Veja qual job falhou
3. Expanda o step que falhou
4. Leia os logs de erro
5. Corrija o problema e faça novo push

---

## 📝 Exemplos de Uso

### Exemplo 1: Criar PR

```bash
git checkout -b feature/nova-funcionalidade
# ... fazer mudanças ...
git add .
git commit -m "Adiciona nova funcionalidade"
git push origin feature/nova-funcionalidade
# Abrir PR no GitHub
```

**O que acontece:**
- ✅ CI executa e valida sintaxe
- ✅ PR Validation adiciona labels
- ✅ Comentário com estatísticas é adicionado
- ✅ Se tudo OK, PR pode ser merged

### Exemplo 2: Adicionar Migração

```bash
# Criar nova migração
vim migrations/add_nova_tabela.php

git add migrations/add_nova_tabela.php
git commit -m "Adiciona migração para nova tabela"
git push
```

**O que acontece:**
- ✅ Database Migrations valida a migração
- ✅ Testa em SQLite e PostgreSQL
- ✅ Comenta no PR com instruções
- ✅ Após merge, lembra de executar no servidor

### Exemplo 3: Deploy para Produção

```bash
# Merge PR na main
git checkout main
git pull origin main
```

**O que acontece:**
- ✅ CI valida tudo novamente
- ✅ Deploy prepara artefatos
- ✅ Deploy via FTP/SSH (se configurado)
- ✅ Health checks executam
- ✅ Release é criada
- ✅ Notificações enviadas

---

## 🔒 Segurança

### O que as Actions NÃO Fazem

- ❌ Não commitam arquivos sensíveis
- ❌ Não expõem secrets nos logs
- ❌ Não fazem deploy sem validação
- ❌ Não sobrescrevem arquivos de configuração

### O que as Actions Verificam

- ✅ Sintaxe PHP válida
- ✅ Estrutura de pastas correta
- ✅ Ausência de credenciais hardcoded
- ✅ Integridade das migrações
- ✅ Tamanho do PR (avisa se muito grande)

---

## 🛠️ Manutenção

### Atualizar Versões

As actions usam versões específicas de ferramentas:

```yaml
uses: actions/checkout@v4          # Verificar se há v5
uses: shivammathur/setup-php@v2    # OK
```

### Adicionar Nova Action

1. Crie arquivo `.github/workflows/nome.yml`
2. Defina triggers (`on:`)
3. Defina jobs e steps
4. Teste localmente com [act](https://github.com/nektos/act)
5. Commit e push

### Desabilitar Action

1. Abra o arquivo `.yml`
2. Adicione no início:
   ```yaml
   # DESABILITADO - motivo
   ```
3. Ou delete o arquivo

---

## 📚 Recursos

- [Documentação GitHub Actions](https://docs.github.com/actions)
- [Marketplace de Actions](https://github.com/marketplace?type=actions)
- [Testar localmente com act](https://github.com/nektos/act)

---

## 🎓 Aprendizado

### Para Iniciantes

As actions deste projeto são um bom ponto de partida para aprender:

1. **Básico:** Comece com `ci.yml` - validação simples
2. **Intermediário:** `pr-validation.yml` - labels e comentários
3. **Avançado:** `deploy.yml` - deploy e releases

### Dicas

- Use `echo` para debug: `run: echo "Debug: ${{ github.ref }}"`
- Teste localmente antes de commitar
- Leia os logs com atenção
- Actions são YAML - indentação importa!

---

## 💡 Melhorias Futuras

- [ ] Testes automatizados com PHPUnit
- [ ] Code coverage reports
- [ ] Performance tests
- [ ] Scan de dependências (npm audit, composer audit)
- [ ] Deploy staging automático
- [ ] Rollback automático em caso de falha
- [ ] Slack/Discord webhooks
- [ ] Lighthouse CI para performance

---

## 🤝 Contribuindo

Para adicionar ou modificar actions:

1. Teste localmente primeiro
2. Documente mudanças neste README
3. Adicione comentários no código YAML
4. Faça PR com descrição detalhada

---

**Dúvidas?** Abra uma issue ou consulte a [documentação oficial](https://docs.github.com/actions).
