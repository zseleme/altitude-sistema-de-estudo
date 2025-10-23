# 🚂 Deploy no Railway.app - Guia Completo

## 🎯 Por que Railway?

- ✅ **Grátis para começar** ($5/mês de créditos)
- ✅ **Suporte nativo a PHP** e PostgreSQL
- ✅ **Deploy automático** via GitHub
- ✅ **HTTPS gratuito** e automático
- ✅ **Fácil de usar** (sem configuração complexa)
- ✅ **Escalável** conforme sua necessidade

---

## 📋 Pré-requisitos

1. ✅ Conta no GitHub (com repositório criado)
2. ✅ Projeto no GitHub (fazer push primeiro)
3. 🆕 Conta no Railway (gratuita)

---

## 🚀 Passo a Passo

### **1️⃣ Criar Conta no Railway**

1. Acesse: https://railway.app
2. Clique em **"Start a New Project"**
3. Faça login com **GitHub** (recomendado)
4. Autorize o Railway a acessar seus repositórios

---

### **2️⃣ Criar Novo Projeto**

1. No dashboard, clique em **"New Project"**
2. Selecione **"Deploy from GitHub repo"**
3. Escolha seu repositório: `seu-usuario/lms-sistema`
4. Railway detectará automaticamente que é um projeto PHP

---

### **3️⃣ Adicionar Banco de Dados PostgreSQL**

1. No projeto, clique em **"+ New"**
2. Selecione **"Database"** → **"Add PostgreSQL"**
3. Railway criará automaticamente um banco PostgreSQL
4. As credenciais serão geradas automaticamente

---

### **4️⃣ Configurar Variáveis de Ambiente**

1. Clique no serviço PHP (não no banco)
2. Vá em **"Variables"**
3. Adicione as seguintes variáveis:

```bash
DB_TYPE=postgresql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_NAME=${{Postgres.PGDATABASE}}
DB_USER=${{Postgres.PGUSER}}
DB_PASS=${{Postgres.PGPASSWORD}}
DB_SCHEMA=estudos
```

**Nota:** Railway substituirá automaticamente `${{Postgres.*}}` pelas credenciais reais.

---

### **5️⃣ Criar Arquivo de Configuração do Railway**

Crie um arquivo `railway.json` na raiz do projeto:

```json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t .",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

Ou crie um `nixpacks.toml`:

```toml
[phases.setup]
nixPkgs = ['php82', 'php82Packages.composer']

[phases.install]
cmds = ['echo "PHP installed"']

[start]
cmd = 'php -S 0.0.0.0:$PORT -t .'
```

---

### **6️⃣ Modificar `config/database.php`**

Atualize para usar variáveis de ambiente:

```php
<?php
// Configuração do tipo de banco de dados
define('DB_TYPE', getenv('DB_TYPE') ?: 'postgresql');

if (DB_TYPE === 'postgresql') {
    // Usar variáveis de ambiente do Railway
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '5432');
    define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
    define('DB_USER', getenv('DB_USER') ?: 'postgres');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_SCHEMA', getenv('DB_SCHEMA') ?: 'estudos');
} else {
    define('DB_PATH', __DIR__ . '/estudos.db');
}

// ... resto do código Database class ...
?>
```

---

### **7️⃣ Executar Setup do Banco**

Após o deploy inicial:

1. No Railway, clique no serviço PostgreSQL
2. Vá em **"Data"** ou **"Query"**
3. Execute o SQL ou use Railway CLI:

```bash
# Instalar Railway CLI
npm i -g @railway/cli

# Login
railway login

# Conectar ao projeto
railway link

# Executar setup
railway run php setup_postgres.php
```

**Ou crie um script de inicialização automática:**

Crie `railway-init.sh`:

```bash
#!/bin/bash
echo "Verificando se banco está configurado..."

php setup_postgres.php

echo "Setup concluído!"
```

E adicione permissão:
```bash
chmod +x railway-init.sh
```

---

### **8️⃣ Deploy Automático**

1. Faça commit das mudanças:
```bash
git add .
git commit -m "🚂 Configura deploy para Railway"
git push origin main
```

2. Railway detectará automaticamente e fará deploy!

---

## 🔧 Arquivos Necessários

### **`railway.json`** (criar na raiz)
```json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t .",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### **`.railwayignore`** (opcional)
```
*.db
*.sqlite
/config/estudos.db
*.log
/vendor/
node_modules/
```

---

## 📊 Variáveis de Ambiente no Railway

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `DB_TYPE` | `postgresql` | Tipo de banco |
| `DB_HOST` | `${{Postgres.PGHOST}}` | Host PostgreSQL |
| `DB_PORT` | `${{Postgres.PGPORT}}` | Porta (5432) |
| `DB_NAME` | `${{Postgres.PGDATABASE}}` | Nome do banco |
| `DB_USER` | `${{Postgres.PGUSER}}` | Usuário |
| `DB_PASS` | `${{Postgres.PGPASSWORD}}` | Senha |
| `DB_SCHEMA` | `estudos` | Schema personalizado |

---

## 🌐 Domínio Personalizado

### Usar domínio do Railway (gratuito)
1. Railway gera automaticamente: `seu-projeto.up.railway.app`
2. Já com HTTPS habilitado

### Usar seu próprio domínio
1. No projeto, vá em **"Settings"** → **"Domains"**
2. Clique em **"Add Domain"**
3. Digite seu domínio: `meu-lms.com.br`
4. Adicione os registros DNS no seu provedor:
   - **Tipo A** → IP fornecido pelo Railway
   - Ou **CNAME** → endereço fornecido

---

## 💰 Custos

### Plano Gratuito (Hobby)
- **$5/mês em créditos** grátis
- Suficiente para:
  - 1 serviço web pequeno
  - 1 banco PostgreSQL pequeno
  - ~500MB RAM
  - ~500 horas/mês

### Quando cobrar
- Se ultrapassar $5/mês em uso
- Você pode definir limite de gastos
- Notificações quando atingir 80% do limite

### Custos típicos
- **Serviço PHP básico**: ~$2-3/mês
- **PostgreSQL pequeno**: ~$1-2/mês
- **Total estimado**: $3-5/mês (pode ficar no free tier!)

---

## 🔍 Monitoramento

### Logs em tempo real
```bash
# Ver logs
railway logs

# Logs com filtro
railway logs --service web
```

### No Dashboard
1. Clique no serviço
2. Vá em **"Deployments"**
3. Clique no deployment ativo
4. Veja logs em tempo real

---

## 🆘 Troubleshooting

### ❌ Erro: "No buildpack found"
**Solução:** Criar `nixpacks.toml` ou `railway.json`

### ❌ Erro: "Database connection failed"
**Solução:** 
1. Verificar variáveis de ambiente
2. Garantir que PostgreSQL está ativo
3. Executar `setup_postgres.php`

### ❌ Deploy lento
**Solução:**
1. Adicionar `.railwayignore`
2. Excluir arquivos desnecessários

### ❌ Aplicação reinicia constantemente
**Solução:**
1. Verificar logs: `railway logs`
2. Corrigir erros de sintaxe
3. Aumentar timeout se necessário

---

## 📱 Railway CLI - Comandos Úteis

```bash
# Instalar
npm i -g @railway/cli

# Login
railway login

# Listar projetos
railway list

# Conectar ao projeto
railway link

# Ver variáveis
railway variables

# Executar comando no ambiente Railway
railway run php setup_postgres.php

# Ver logs
railway logs

# Abrir no navegador
railway open
```

---

## 🔄 CI/CD Automático

Railway já tem CI/CD integrado!

**Workflow automático:**
```
git push → GitHub → Railway detecta → Build → Deploy → Live!
```

**Recursos:**
- ✅ Deploy automático a cada push
- ✅ Preview deployments para PRs
- ✅ Rollback com 1 clique
- ✅ Histórico de deploys

---

## 🎯 Checklist de Deploy

- [ ] Conta Railway criada
- [ ] Repositório no GitHub
- [ ] Push do código para GitHub
- [ ] Projeto criado no Railway
- [ ] PostgreSQL adicionado
- [ ] Variáveis de ambiente configuradas
- [ ] `railway.json` criado
- [ ] `config/database.php` atualizado para env vars
- [ ] Commit e push das mudanças
- [ ] Deploy automático executado
- [ ] `setup_postgres.php` executado
- [ ] Testado no navegador

---

## 🌟 Vantagens do Railway

1. **Deploy em 5 minutos** (setup inicial)
2. **Zero configuração** de servidor
3. **HTTPS automático** e gratuito
4. **Escalável** com 1 clique
5. **Preview environments** para testes
6. **Rollback fácil** se algo der errado
7. **Logs em tempo real**
8. **Banco PostgreSQL gerenciado**
9. **Suporte a múltiplos ambientes** (dev, staging, prod)
10. **Interface moderna** e intuitiva

---

## 📞 Suporte

- **Documentação**: https://docs.railway.app
- **Discord**: https://discord.gg/railway
- **Twitter**: @Railway

---

🎉 **Pronto!** Seu LMS estará online com deploy automático!

**URL exemplo:** `https://seu-lms.up.railway.app`

