# GitHub Actions - Altitude Sistema de Estudos

Este diretório contém as workflows (actions) automatizadas do projeto.

## Workflows Disponíveis

### Deploy FTP Automático (`ftp-deploy.yml`)

**Quando executa:** A cada push nas branches `main` ou `develop`

**O que faz:**
1. **Validação PHP** - Verifica sintaxe de todos os arquivos PHP
2. **Deploy Paralelo** - Envia para múltiplos servidores FTP simultaneamente
3. **Versionamento** - Cria `version.json` com informações do deploy

**Características:**
- Suporta até 3 servidores FTP (expansível)
- Deploy em paralelo (servidores independentes)
- Servidores não configurados são ignorados
- Validação PHP antes do deploy (evita deploy de código com erro)
- `fail-fast: false` - falha em um servidor não afeta os outros

**Status:** ✅ Ativo

**Documentação completa:** [FTP_DEPLOY.md](FTP_DEPLOY.md)

---

## Configuração Rápida

### Secrets Necessários

Vá em **Settings → Secrets and variables → Actions → Secrets**

| Secret | Descrição |
|--------|-----------|
| `FTP_SERVER_1` | Host do servidor FTP principal |
| `FTP_USERNAME_1` | Usuário FTP |
| `FTP_PASSWORD_1` | Senha FTP |
| `FTP_SERVER_2` | Host do servidor FTP secundário (opcional) |
| `FTP_USERNAME_2` | Usuário FTP (opcional) |
| `FTP_PASSWORD_2` | Senha FTP (opcional) |

### Variables Necessárias

Vá em **Settings → Secrets and variables → Actions → Variables**

| Variable | Descrição |
|----------|-----------|
| `FTP_PATH_1_MAIN` | Path de produção no servidor 1 |
| `FTP_PATH_1_DEV` | Path de desenvolvimento no servidor 1 |
| `FTP_URL_1_MAIN` | URL de produção (para logs) |
| `FTP_URL_1_DEV` | URL de desenvolvimento (para logs) |

---

## Como Usar

### Deploy para Produção

```bash
git checkout main
git merge develop
git push origin main
```

### Deploy para Desenvolvimento

```bash
git checkout develop
git push origin develop
```

---

## Verificar Status

1. Vá em **Actions** no GitHub
2. Clique no workflow **"Deploy FTP Automático"**
3. Veja os jobs em execução (um por servidor)

### Status dos Jobs

| Status | Significado |
|--------|-------------|
| ✅ Success | Deploy concluído |
| ❌ Failure | Deploy falhou |
| ⏭️ Skipped | Servidor não configurado |
| 🟡 Running | Em execução |

---

## Arquivos Excluídos

Os seguintes arquivos **não são enviados** ao servidor:

- `.git/`, `.github/` - Controle de versão
- `node_modules/`, `vendor/` - Dependências
- `.env`, `config/database.php` - Configuração local
- `config/estudos.db` - Banco SQLite
- `config/encryption.key` - Chave de criptografia
- `tests/`, `*.md` - Testes e documentação

---

## Troubleshooting

### Deploy não executa

1. Verifique se está na branch correta (`main` ou `develop`)
2. Confirme que os secrets estão configurados
3. Veja se Actions está habilitado no repositório

### Erro de sintaxe PHP

```bash
# Teste localmente antes de fazer push
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
```

### Erro de conexão FTP

1. Teste credenciais manualmente
2. Verifique se o servidor aceita conexões FTP
3. Confirme que o firewall não está bloqueando

---

## Estrutura

```
.github/
├── README.md           # Este arquivo
├── FTP_DEPLOY.md       # Documentação detalhada do deploy
└── workflows/
    └── ftp-deploy.yml  # Workflow de deploy automático
```

---

## Links Úteis

- [Documentação GitHub Actions](https://docs.github.com/actions)
- [FTP Deploy Action](https://github.com/SamKirkland/FTP-Deploy-Action)

---

**Dúvidas?** Consulte [FTP_DEPLOY.md](FTP_DEPLOY.md) para documentação completa.
