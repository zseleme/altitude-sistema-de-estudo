# 🚀 Deploy FTP Automático

## Como Funciona

O sistema está configurado para fazer deploy automático via FTP:

### 📁 Ambientes

| Branch | Pasta FTP | Ambiente |
|--------|-----------|----------|
| `main` | `/public_html/producao` | **Produção** |
| `develop` | `/public_html/desenvolvimento` | **Desenvolvimento** |

### 🔄 Fluxo Automático

```
Push na main → GitHub Action → Validação → Deploy FTP → /public_html/producao
Push na develop → GitHub Action → Validação → Deploy FTP → /public_html/desenvolvimento
```

## ⚙️ Configuração

### 1. Secrets Configurados ✅

Você já configurou os secrets necessários:

- ✅ `FTP_SERVER` - Endereço do servidor FTP
- ✅ `FTP_USERNAME` - Usuário FTP
- ✅ `FTP_PASSWORD` - Senha FTP

### 2. Ajustar Pastas de Destino

Se suas pastas FTP forem diferentes, edite o arquivo `.github/workflows/ftp-deploy.yml`:

```yaml
# Linha ~34
if [ "${{ github.ref }}" == "refs/heads/main" ]; then
  echo "folder=/public_html/producao" >> $GITHUB_OUTPUT  # ← Altere aqui
  # ...
elif [ "${{ github.ref }}" == "refs/heads/develop" ]; then
  echo "folder=/public_html/desenvolvimento" >> $GITHUB_OUTPUT  # ← Altere aqui
```

### 3. Ajustar URLs (opcional)

No final do arquivo, altere as URLs:

```yaml
# Linha ~120
echo "🌐 Acesse:"
echo "  • Produção: https://seu-site.com"  # ← Seu domínio
echo "  • Desenvolvimento: https://dev.seu-site.com"  # ← Seu subdomínio
```

## 📝 Como Usar

### Deploy para Produção

```bash
# Trabalhe na sua branch
git checkout -b feature/nova-funcionalidade

# Faça suas mudanças
git add .
git commit -m "Adiciona nova funcionalidade"

# Merge na main (via PR ou direto)
git checkout main
git merge feature/nova-funcionalidade
git push origin main
```

**Resultado:** Deploy automático para `/public_html/producao` 🚀

### Deploy para Desenvolvimento

```bash
# Merge na develop
git checkout develop
git merge feature/nova-funcionalidade
git push origin develop
```

**Resultado:** Deploy automático para `/public_html/desenvolvimento` 🚀

## 🔍 Verificar Status do Deploy

1. Vá em **Actions** no GitHub
2. Procure pelo workflow **"Deploy FTP Automático"**
3. Clique no último execution
4. Veja os logs de cada etapa

### Status Possíveis

- ✅ **Success** - Deploy concluído com sucesso
- ❌ **Failure** - Deploy falhou (veja os logs)
- 🟡 **In Progress** - Deploy em andamento
- ⏸️ **Cancelled** - Deploy cancelado

## 📂 Arquivos Enviados

### ✅ O que É Enviado

Todos os arquivos do projeto, exceto:

### ❌ O que NÃO É Enviado

- `.git/` - Histórico do Git
- `node_modules/` - Dependências Node
- `vendor/` - Dependências PHP
- `.env` - Variáveis de ambiente
- `config/estudos.db` - Banco SQLite local
- `config/database.php` - Configuração do banco
- `config/openai.php` - Chaves da API
- `.github/` - Workflows
- `tests/` - Testes
- `*.md` - Arquivos de documentação

## 📊 Arquivo de Versão

Após cada deploy, são criados no servidor:

### `version.json`
```json
{
  "version": "2024.12.04-153045",
  "commit": "abc123def456",
  "branch": "main",
  "deployed_at": "2024-12-04T15:30:45Z",
  "deployed_by": "seu-usuario",
  "environment": "Produção"
}
```

### `LAST_DEPLOY.txt`
```
====================================
ÚLTIMO DEPLOY
====================================
Ambiente: Produção
Branch: main
Commit: abc123def456
Data: Wed Dec 4 15:30:45 UTC 2024
Por: seu-usuario
====================================
```

## 🛠️ Troubleshooting

### Erro: "Syntax check failed"

**Causa:** Há erros de sintaxe no código PHP

**Solução:**
```bash
# Teste localmente
find . -name "*.php" -exec php -l {} \;

# Corrija os erros e tente novamente
```

### Erro: "FTP connection failed"

**Causas possíveis:**
- Servidor FTP offline
- Credenciais incorretas
- Firewall bloqueando

**Soluções:**
1. Teste credenciais manualmente:
   ```bash
   ftp seu-servidor.com
   # Digite usuário e senha
   ```

2. Verifique os secrets no GitHub:
   - Settings → Secrets → Actions
   - Confirme FTP_SERVER, FTP_USERNAME, FTP_PASSWORD

### Erro: "Permission denied"

**Causa:** Sem permissão para escrever na pasta

**Solução:**
- Verifique permissões da pasta no servidor
- Certifique-se que o usuário FTP tem acesso de escrita

### Deploy não acontece

**Verifique:**
1. Branch está correta? (main ou develop)
2. Push foi feito? (`git push origin main`)
3. Action está habilitada? (Actions → Workflows)

## 🔒 Segurança

### ✅ Boas Práticas Implementadas

- Arquivos sensíveis são excluídos do deploy
- Secrets são criptografados no GitHub
- Validação de sintaxe antes do deploy
- Logs não expõem senhas

### ⚠️ Importante

1. **Nunca commite:**
   - Senhas
   - Chaves de API
   - Arquivos `.env`
   - Banco de dados

2. **No servidor, configure:**
   - Crie `config/database.php` manualmente
   - Crie `config/openai.php` se usar IA
   - Configure permissões corretas (755 para pastas, 644 para arquivos)

3. **Após primeiro deploy:**
   - Acesse o site e siga a instalação automática
   - Login: admin@teste.com / admin123
   - **Altere a senha imediatamente**

## 📋 Checklist Pós-Deploy

Após cada deploy em produção:

- [ ] Acessar o site e verificar se está online
- [ ] Testar login
- [ ] Verificar se banco de dados foi criado
- [ ] Testar funcionalidades críticas
- [ ] Verificar logs de erro do servidor
- [ ] Confirmar que arquivos foram atualizados (ver `LAST_DEPLOY.txt`)

## 🚀 Próximos Passos

### Melhorias Possíveis

1. **Adicionar stage de staging:**
   ```yaml
   - staging  # Branch staging
   ```

2. **Notificações:**
   - Discord
   - Slack
   - Email

3. **Rollback automático:**
   - Detectar erros 500
   - Reverter para versão anterior

4. **Testes automatizados:**
   - Executar antes do deploy
   - Cancelar deploy se falhar

## 📞 Suporte

**Problemas com deploy?**

1. Veja os logs em Actions
2. Confira este guia
3. Teste credenciais manualmente
4. Abra uma issue se necessário

---

**Status:** ✅ Deploy automático configurado e funcionando!
