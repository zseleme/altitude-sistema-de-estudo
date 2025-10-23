# 🚀 Guia de Deploy no GitHub

Este guia irá ajudá-lo a subir seu projeto para o GitHub.

## 📋 Pré-requisitos

1. ✅ Ter uma conta no GitHub (https://github.com)
2. ✅ Git instalado localmente (já configurado)
3. ✅ Repositório local inicializado (✅ CONCLUÍDO)

## 🔧 Passo a Passo

### 1️⃣ Criar Repositório no GitHub

1. Acesse https://github.com
2. Clique no botão **"+"** no canto superior direito
3. Selecione **"New repository"**
4. Preencha:
   - **Repository name**: `lms-sistema` (ou o nome que preferir)
   - **Description**: "Sistema LMS - Plataforma de Ensino Online"
   - **Visibility**: Escolha **Public** ou **Private**
   - ⚠️ **NÃO** marque "Initialize this repository with a README"
   - ⚠️ **NÃO** adicione .gitignore ou license (já temos)
5. Clique em **"Create repository"**

### 2️⃣ Conectar ao Repositório Remoto

Após criar o repositório, o GitHub mostrará comandos. Use estes:

```bash
# Adicionar o remote (substitua SEU-USUARIO e SEU-REPOSITORIO)
git remote add origin https://github.com/SEU-USUARIO/SEU-REPOSITORIO.git

# Verificar se foi adicionado
git remote -v

# Fazer o push inicial
git push -u origin main
```

**Exemplo:**
```bash
git remote add origin https://github.com/joaosilva/lms-sistema.git
git push -u origin main
```

### 3️⃣ Autenticação

Quando fizer o push, o Git pedirá suas credenciais:

#### Opção A: HTTPS com Token (Recomendado)
1. No GitHub, vá em **Settings** > **Developer settings** > **Personal access tokens** > **Tokens (classic)**
2. Clique em **"Generate new token"** > **"Generate new token (classic)"**
3. Dê um nome: "LMS Deploy"
4. Selecione o escopo: **repo** (marque todas as opções de repo)
5. Clique em **"Generate token"**
6. **COPIE O TOKEN** (você não verá novamente!)
7. Use como senha quando o Git pedir

**Credenciais:**
- Username: seu-usuario-github
- Password: cole-o-token-gerado

#### Opção B: SSH (Avançado)
```bash
# Gerar chave SSH
ssh-keygen -t ed25519 -C "seu-email@example.com"

# Adicionar ao ssh-agent
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519

# Copiar a chave pública
cat ~/.ssh/id_ed25519.pub

# Adicionar no GitHub: Settings > SSH and GPG keys > New SSH key
# Cole a chave copiada

# Alterar remote para SSH
git remote set-url origin git@github.com:SEU-USUARIO/SEU-REPOSITORIO.git
```

### 4️⃣ Push para o GitHub

```bash
# Push inicial (já com upstream configurado)
git push -u origin main
```

Após o primeiro push, basta usar:
```bash
git push
```

## 📝 Comandos Úteis

### Fazer alterações e commit
```bash
# Ver status
git status

# Adicionar arquivos modificados
git add .

# Ou adicionar arquivo específico
git add arquivo.php

# Fazer commit
git commit -m "Descrição da mudança"

# Enviar para o GitHub
git push
```

### Ver histórico
```bash
# Ver commits
git log

# Ver commits resumidos
git log --oneline

# Ver diferenças
git diff
```

### Branches
```bash
# Criar nova branch
git checkout -b feature/nova-funcionalidade

# Listar branches
git branch

# Trocar de branch
git checkout main

# Fazer merge
git merge feature/nova-funcionalidade

# Enviar branch para o GitHub
git push origin feature/nova-funcionalidade
```

### Desfazer mudanças
```bash
# Desfazer alterações não commitadas
git checkout -- arquivo.php

# Desfazer último commit (mantém alterações)
git reset --soft HEAD~1

# Desfazer último commit (descarta alterações)
git reset --hard HEAD~1
```

## 🔒 Segurança - IMPORTANTE!

### ⚠️ Arquivos já protegidos pelo .gitignore:
- ✅ `config/database.php` (credenciais do banco)
- ✅ `*.db` e `*.sqlite` (bancos de dados)
- ✅ `*.log` (logs)
- ✅ `/uploads/*` (arquivos enviados)

### ⚠️ NUNCA commite:
- Senhas ou credenciais
- Tokens de API
- Chaves privadas
- Dados sensíveis de usuários

### ✅ Se acidentalmente commitou algo sensível:
```bash
# Remover arquivo do histórico (use com cuidado!)
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch config/database.php" \
  --prune-empty --tag-name-filter cat -- --all

# Forçar push (CUIDADO: reescreve histórico)
git push origin --force --all
```

## 📦 Workflow Recomendado

### Desenvolvimento
```bash
# 1. Criar branch para nova feature
git checkout -b feature/modo-escuro

# 2. Fazer alterações e testar
# ... código ...

# 3. Adicionar e commitar
git add .
git commit -m "✨ Adiciona modo escuro"

# 4. Push da branch
git push origin feature/modo-escuro

# 5. No GitHub, criar Pull Request
# 6. Após aprovação, fazer merge para main
```

### Produção
```bash
# Sempre trabalhar em branches
# Nunca commitar direto na main
# Usar Pull Requests para revisão
# Testar antes de fazer merge
```

## 🏷️ Convenção de Commits (Recomendado)

Use emojis e prefixos para commits mais claros:

```bash
git commit -m "✨ feat: Nova funcionalidade X"
git commit -m "🐛 fix: Corrige bug Y"
git commit -m "📝 docs: Atualiza README"
git commit -m "♻️ refactor: Refatora código Z"
git commit -m "💄 style: Melhora visual da página"
git commit -m "⚡ perf: Melhora performance"
git commit -m "✅ test: Adiciona testes"
git commit -m "🔧 chore: Atualiza dependências"
```

### Emojis úteis:
- ✨ `:sparkles:` - Nova feature
- 🐛 `:bug:` - Correção de bug
- 📝 `:memo:` - Documentação
- ♻️ `:recycle:` - Refatoração
- 💄 `:lipstick:` - UI/estilo
- ⚡ `:zap:` - Performance
- 🔒 `:lock:` - Segurança
- 🚀 `:rocket:` - Deploy
- 🔧 `:wrench:` - Configuração

## 📊 Status Atual

✅ **Repositório Git inicializado**
✅ **Branch main criada**
✅ **Primeiro commit realizado** (35 arquivos, 8452 linhas)
✅ **.gitignore configurado**
✅ **README.md criado**
✅ **Arquivos sensíveis protegidos**

### Próximos passos:
1. ⏳ Criar repositório no GitHub
2. ⏳ Adicionar remote
3. ⏳ Fazer push inicial

## 🆘 Problemas Comuns

### "Permission denied"
- Verifique se o token está correto
- Ou configure SSH corretamente

### "Remote already exists"
```bash
git remote remove origin
git remote add origin <novo-url>
```

### "Push rejected"
```bash
# Puxar mudanças primeiro
git pull origin main --rebase
git push
```

### "Merge conflict"
```bash
# Ver conflitos
git status

# Resolver manualmente nos arquivos
# Depois:
git add .
git commit -m "Resolve conflitos"
git push
```

## 📞 Ajuda

- Documentação Git: https://git-scm.com/doc
- GitHub Docs: https://docs.github.com
- GitHub Guides: https://guides.github.com

---

🎉 **Sucesso!** Seu projeto está pronto para ser publicado no GitHub!

