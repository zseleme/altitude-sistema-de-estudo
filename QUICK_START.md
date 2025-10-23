# ⚡ Quick Start - Subir para o GitHub

## 🎯 Comandos Rápidos (Copy & Paste)

### 1. Criar repositório no GitHub
👉 Acesse: https://github.com/new

### 2. Conectar e fazer push

```bash
# SUBSTITUA os valores abaixo:
# - SEU-USUARIO: seu nome de usuário do GitHub
# - SEU-REPOSITORIO: nome que você deu ao repositório

git remote add origin https://github.com/SEU-USUARIO/SEU-REPOSITORIO.git
git push -u origin main
```

### Exemplo prático:
```bash
# Se seu usuário for "joaosilva" e o repo "lms-sistema":
git remote add origin https://github.com/joaosilva/lms-sistema.git
git push -u origin main
```

## 🔑 Autenticação

Quando pedir senha, use um **Personal Access Token**:

1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Marque: **repo** (todas as opções)
4. Generate token
5. **COPIE O TOKEN** (você só verá uma vez!)

Credenciais:
- **Username**: seu-usuario-github
- **Password**: cole-o-token

## 📋 Checklist

- [x] ✅ Git inicializado
- [x] ✅ Primeiro commit feito
- [x] ✅ Arquivos sensíveis protegidos (.gitignore)
- [ ] ⏳ Criar repositório no GitHub
- [ ] ⏳ Adicionar remote
- [ ] ⏳ Fazer push

## 🎉 Pronto!

Após o push, seu código estará no GitHub!

Acesse: `https://github.com/SEU-USUARIO/SEU-REPOSITORIO`

---

📖 Para mais detalhes, veja: [DEPLOY_GUIDE.md](DEPLOY_GUIDE.md)

