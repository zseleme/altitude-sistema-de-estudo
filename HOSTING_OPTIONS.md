# 🌐 Opções de Hospedagem para o Projeto LMS

## 📊 Comparação Rápida

| Plataforma | Grátis? | PHP | PostgreSQL | Facilidade | Deploy | Recomendação |
|------------|---------|-----|------------|------------|--------|--------------|
| **Railway** | ✅ $5/mês créditos | ✅ | ✅ Gerenciado | ⭐⭐⭐⭐⭐ | Git auto | 🏆 **MELHOR** |
| **Render** | ✅ Limitado | ✅ | ✅ Grátis | ⭐⭐⭐⭐ | Git auto | 🥈 Bom |
| **Heroku** | ❌ $7/mês | ✅ | ✅ Add-on | ⭐⭐⭐⭐⭐ | Git auto | 🥉 Pago mas ótimo |
| **Vercel** | ❌ Não suporta | ❌ | ❌ | - | - | ❌ Incompatível |
| **Netlify** | ❌ Não suporta | ❌ | ❌ | - | - | ❌ Incompatível |
| **VPS** | ❌ $4-10/mês | ✅ | ✅ | ⭐⭐ | Manual | 💻 Controle total |
| **Hostinger** | ❌ ~R$10/mês | ✅ | ✅ | ⭐⭐⭐⭐⭐ | FTP/cPanel | 🏠 Tradicional |

---

## 🏆 Opção 1: Railway.app (RECOMENDADO)

### ✅ Vantagens
- Deploy automático via Git
- PostgreSQL incluído e gerenciado
- $5/mês em créditos grátis (suficiente para começar)
- HTTPS automático
- Logs em tempo real
- CLI poderoso
- Interface moderna

### ❌ Desvantagens
- Pode custar após $5/mês se tráfego alto
- Menos conhecido que Heroku

### 💰 Custo
- **Grátis**: $5/mês em créditos
- **Estimado**: $3-5/mês uso normal
- **Pode ficar 100% grátis** se otimizar

### 📖 Guia
👉 Ver [DEPLOY_RAILWAY.md](DEPLOY_RAILWAY.md)

---

## 🥈 Opção 2: Render.com

### ✅ Vantagens
- Plano gratuito real
- PostgreSQL grátis (limitado)
- Deploy via Git
- HTTPS automático
- Boa documentação

### ❌ Desvantagens
- Plano grátis "dorme" após 15min inatividade
- Primeiro acesso após sleep = lento (30s)
- PostgreSQL grátis expira em 90 dias

### 💰 Custo
- **Grátis**: Com limitações
- **Pago**: $7/mês para web service sempre ativo

### 🔗 Setup
1. https://render.com → New → Web Service
2. Conectar GitHub
3. Build Command: (vazio)
4. Start Command: `php -S 0.0.0.0:$PORT -t .`
5. Add PostgreSQL database
6. Configurar variáveis de ambiente

---

## 🥉 Opção 3: Heroku

### ✅ Vantagens
- Muito maduro e estável
- Documentação excelente
- Grande comunidade
- Add-ons para tudo
- PostgreSQL muito bom

### ❌ Desvantagens
- Não tem mais plano gratuito
- Mais caro que Railway

### 💰 Custo
- **Mínimo**: $7/mês (Eco Dynos)
- **PostgreSQL**: $5/mês add-on
- **Total**: ~$12/mês

### 🔗 Setup
1. https://heroku.com
2. Create new app
3. Add buildpack: `heroku/php`
4. Add PostgreSQL add-on
5. `git push heroku main`

---

## 💻 Opção 4: VPS (Controle Total)

### Provedores Recomendados

#### DigitalOcean
- **$4/mês**: 512MB RAM, 10GB SSD
- **$6/mês**: 1GB RAM, 25GB SSD
- Dashboard excelente
- Tutoriais detalhados

#### Vultr
- **$2.50/mês**: 512MB RAM
- **$5/mês**: 1GB RAM
- Boa performance
- Várias localizações

#### Contabo
- **€4/mês**: 4GB RAM, 200GB SSD
- Melhor custo-benefício
- Servidores na Alemanha

### ✅ Vantagens
- Controle total
- Root access
- Pode hospedar múltiplos projetos
- Performance previsível

### ❌ Desvantagens
- Requer conhecimento técnico
- Você gerencia tudo (updates, segurança, backups)
- Mais trabalhoso

### 🛠️ Setup Básico
```bash
# 1. Conectar ao servidor
ssh root@seu-ip

# 2. Atualizar sistema
apt update && apt upgrade -y

# 3. Instalar stack
apt install nginx php8.2-fpm php8.2-pgsql postgresql git -y

# 4. Configurar Nginx
# 5. Clonar projeto
# 6. Configurar banco
# 7. Configurar SSL (Let's Encrypt)
```

---

## 🏠 Opção 5: Hospedagem Compartilhada

### Provedores no Brasil

#### Hostinger
- ~R$10/mês
- cPanel
- PHP, MySQL/PostgreSQL
- SSL grátis
- Muito fácil

#### Hostgator
- ~R$20/mês
- cPanel
- Suporte em PT-BR
- Bom para iniciantes

#### Umbler
- R$19/mês
- Dashboard moderno
- Deploy via Git
- PostgreSQL incluído

### ✅ Vantagens
- Muito fácil (cPanel)
- Suporte em português
- Backups automáticos
- SSL incluído

### ❌ Desvantagens
- Performance limitada
- Recursos compartilhados
- Menos flexibilidade

---

## 🎯 Qual Escolher?

### Para Começar (Aprendizado/Portfolio)
→ **Railway.app** (grátis/barato + fácil)

### Para Produção Pequena
→ **Railway** ou **Render** (confiável + barato)

### Para Produção Empresarial
→ **Heroku** ou **VPS** (estabilidade + suporte)

### Para Iniciantes Totais
→ **Hostinger** (cPanel + suporte PT-BR)

### Para Máximo Controle
→ **VPS** (DigitalOcean, Vultr, Contabo)

---

## 🚫 NÃO Use Para PHP

- ❌ **Vercel** - Não suporta PHP
- ❌ **Netlify** - Apenas sites estáticos
- ❌ **GitHub Pages** - Apenas HTML/CSS/JS estático
- ❌ **Cloudflare Pages** - Apenas estático
- ❌ **Firebase Hosting** - Apenas frontend

---

## 💡 Dica: Começar Grátis

### Estratégia Recomendada

1. **Desenvolvimento**: SQLite local
2. **Staging/Testes**: Railway (grátis)
3. **Produção inicial**: Railway ($5/mês)
4. **Crescimento**: Migrar para VPS ou Heroku

### Stack Grátis Total
- **Code**: GitHub (grátis)
- **Hosting**: Railway ($5 créditos/mês)
- **Database**: Railway PostgreSQL (incluído)
- **Domain**: Usar subdomínio Railway (grátis)
- **SSL**: Automático (grátis)
- **CDN**: Cloudflare (grátis)

**Total**: $0/mês (até acabar os $5 de créditos)

---

## 📱 Railway CLI - Quick Start

```bash
# Instalar
npm i -g @railway/cli

# Login
railway login

# Criar projeto
railway init

# Deploy
railway up

# Ver logs
railway logs

# Abrir no navegador
railway open
```

---

## 🔗 Links Úteis

- **Railway**: https://railway.app
- **Render**: https://render.com
- **Heroku**: https://heroku.com
- **DigitalOcean**: https://digitalocean.com
- **Vultr**: https://vultr.com
- **Contabo**: https://contabo.com
- **Hostinger**: https://hostinger.com.br
- **Umbler**: https://umbler.com

---

## ✅ Checklist de Deploy

Independente da plataforma escolhida:

- [ ] Código no GitHub
- [ ] `.gitignore` configurado
- [ ] Variáveis de ambiente configuradas
- [ ] Banco de dados criado
- [ ] Schema/tabelas criadas
- [ ] Testes básicos funcionando
- [ ] SSL/HTTPS configurado
- [ ] Domínio apontado (se tiver)
- [ ] Backups configurados
- [ ] Monitoramento ativo

---

🎉 **Pronto para escolher e fazer deploy!**

Recomendação: Comece com **Railway.app** → É grátis, fácil e perfeito para este projeto!

