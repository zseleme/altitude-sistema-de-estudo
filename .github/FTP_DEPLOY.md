# Deploy FTP Automático

## Como Funciona

O sistema está configurado para fazer deploy automático via FTP com suporte a **múltiplos servidores** em paralelo.

### Ambientes

| Branch | Ambiente | Descrição |
|--------|----------|-----------|
| `main` | **Produção** | Deploy para servidores de produção |
| `develop` | **Desenvolvimento** | Deploy para servidores de staging/dev |

### Fluxo Automático

```
Push → Validação PHP → Deploy Paralelo → Múltiplos Servidores
```

1. **Validação PHP** - Verifica sintaxe de todos os arquivos antes do deploy
2. **Deploy Paralelo** - Envia para todos os servidores configurados simultaneamente
3. **Servidores Opcionais** - Servidores não configurados são ignorados automaticamente

## Configuração no GitHub

### 1. Secrets (Credenciais FTP)

Vá em **Settings → Secrets and variables → Actions → Secrets**

#### Servidor 1 (Principal)
| Secret | Descrição | Exemplo |
|--------|-----------|---------|
| `FTP_SERVER_1` | Host do servidor FTP | `ftp.meusite.com` |
| `FTP_USERNAME_1` | Usuário FTP | `deploy@meusite.com` |
| `FTP_PASSWORD_1` | Senha FTP | `********` |

#### Servidor 2 (Opcional)
| Secret | Descrição | Exemplo |
|--------|-----------|---------|
| `FTP_SERVER_2` | Host do servidor FTP | `ftp.outrosite.com` |
| `FTP_USERNAME_2` | Usuário FTP | `deploy@outrosite.com` |
| `FTP_PASSWORD_2` | Senha FTP | `********` |

#### Servidor 3 (Opcional)
| Secret | Descrição | Exemplo |
|--------|-----------|---------|
| `FTP_SERVER_3` | Host do servidor FTP | `ftp.terceiro.com` |
| `FTP_USERNAME_3` | Usuário FTP | `deploy@terceiro.com` |
| `FTP_PASSWORD_3` | Senha FTP | `********` |

### 2. Variables (Paths e URLs)

Vá em **Settings → Secrets and variables → Actions → Variables**

#### Servidor 1
| Variable | Descrição | Exemplo |
|----------|-----------|---------|
| `FTP_PATH_1_MAIN` | Path para produção | `/www/meusite.com` |
| `FTP_PATH_1_DEV` | Path para desenvolvimento | `/www/dev.meusite.com` |
| `FTP_URL_1_MAIN` | URL de produção | `https://meusite.com` |
| `FTP_URL_1_DEV` | URL de desenvolvimento | `https://dev.meusite.com` |

#### Servidor 2 (se configurado)
| Variable | Descrição | Exemplo |
|----------|-----------|---------|
| `FTP_PATH_2_MAIN` | Path para produção | `/www/outrosite.com` |
| `FTP_PATH_2_DEV` | Path para desenvolvimento | `/www/dev.outrosite.com` |
| `FTP_URL_2_MAIN` | URL de produção | `https://outrosite.com` |
| `FTP_URL_2_DEV` | URL de desenvolvimento | `https://dev.outrosite.com` |

#### Servidor 3 (se configurado)
| Variable | Descrição | Exemplo |
|----------|-----------|---------|
| `FTP_PATH_3_MAIN` | Path para produção | `/www/terceiro.com` |
| `FTP_PATH_3_DEV` | Path para desenvolvimento | `/www/dev.terceiro.com` |
| `FTP_URL_3_MAIN` | URL de produção | `https://terceiro.com` |
| `FTP_URL_3_DEV` | URL de desenvolvimento | `https://dev.terceiro.com` |

## Exemplo de Configuração

### Cenário: 2 Servidores

**Servidor 1** - Site principal (zaiden.eng.br)
**Servidor 2** - Site secundário (seleme.pt)

#### Secrets:
```
FTP_SERVER_1=ftp.zaiden.eng.br
FTP_USERNAME_1=deploy@zaiden.eng.br
FTP_PASSWORD_1=senha123

FTP_SERVER_2=ftp.seleme.pt
FTP_USERNAME_2=deploy@seleme.pt
FTP_PASSWORD_2=senha456
```

#### Variables:
```
FTP_PATH_1_MAIN=/www/altitude.zaiden.eng.br
FTP_PATH_1_DEV=/www/altitude-dev.zaiden.eng.br
FTP_URL_1_MAIN=https://altitude.zaiden.eng.br
FTP_URL_1_DEV=https://altitude-dev.zaiden.eng.br

FTP_PATH_2_MAIN=/www/seleme.pt
FTP_PATH_2_DEV=/www/dev.seleme.pt
FTP_URL_2_MAIN=https://seleme.pt
FTP_URL_2_DEV=https://dev.seleme.pt
```

## Como Usar

### Deploy para Produção

```bash
git checkout main
git merge develop
git push origin main
```

**Resultado:** Deploy automático para todos os servidores configurados (paths `*_MAIN`)

### Deploy para Desenvolvimento

```bash
git checkout develop
git add .
git commit -m "Nova feature"
git push origin develop
```

**Resultado:** Deploy automático para todos os servidores configurados (paths `*_DEV`)

## Verificar Status do Deploy

1. Vá em **Actions** no GitHub
2. Procure pelo workflow **"Deploy FTP Automático"**
3. Clique na execução mais recente
4. Veja os jobs para cada servidor (executam em paralelo)

### Status Possíveis

- ✅ **Success** - Deploy concluído com sucesso
- ❌ **Failure** - Deploy falhou (veja os logs)
- ⏭️ **Skipped** - Servidor não configurado (ignorado)
- 🟡 **In Progress** - Deploy em andamento

## Arquivos Excluídos do Deploy

Os seguintes arquivos **NÃO são enviados**:

- `.git/` - Histórico do Git
- `.github/` - Workflows e configurações
- `node_modules/` - Dependências Node
- `vendor/` - Dependências PHP (Composer)
- `.env` - Variáveis de ambiente
- `config/estudos.db` - Banco SQLite local
- `config/database.php` - Configuração do banco
- `config/openai.php` - Chaves da API
- `config/encryption.key` - Chave de criptografia
- `tests/` - Arquivos de teste
- `*.md` - Documentação

## Arquivo de Versão

Após cada deploy, é criado um `version.json` no servidor:

```json
{
  "version": "2026.02.16-143045",
  "commit": "abc123def456",
  "branch": "main",
  "deployed_at": "2026-02-16T14:30:45Z",
  "deployed_by": "usuario",
  "environment": "Produção",
  "server": "Servidor Principal"
}
```

## Troubleshooting

### Erro: "Servidor não configurado"

**Causa:** Secrets FTP não estão configurados para este servidor.

**Solução:** Configure `FTP_SERVER_X`, `FTP_USERNAME_X` e `FTP_PASSWORD_X` nos secrets.

### Erro: "Path não configurado"

**Causa:** Variables de path não estão configurados para este branch.

**Solução:** Configure `FTP_PATH_X_MAIN` ou `FTP_PATH_X_DEV` nas variables.

### Erro: "Syntax check failed"

**Causa:** Há erros de sintaxe no código PHP.

**Solução:**
```bash
# Teste localmente
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
# Corrija os erros e faça novo push
```

### Erro: "FTP connection failed"

**Causas possíveis:**
- Servidor FTP offline
- Credenciais incorretas
- Firewall bloqueando conexão

**Soluções:**
1. Teste credenciais manualmente via FTP client
2. Verifique os secrets no GitHub
3. Confirme que o servidor aceita conexões FTP

### Deploy não acontece

**Verifique:**
1. Branch está correta? (main ou develop)
2. Push foi feito? (`git push origin main`)
3. Actions estão habilitadas? (Actions tab)
4. Há secrets configurados? (Settings → Secrets)

## Segurança

### Boas Práticas Implementadas

- Validação de sintaxe PHP antes do deploy
- Arquivos sensíveis são excluídos automaticamente
- Secrets são criptografados pelo GitHub
- Logs não expõem senhas
- Deploy paralelo com `fail-fast: false` (falha em um servidor não afeta os outros)

### Importante

1. **Nunca commite:**
   - Senhas ou chaves de API
   - Arquivos `.env`
   - `config/database.php`
   - `config/encryption.key`

2. **No servidor, configure manualmente:**
   - `config/database.php` com credenciais do banco
   - Variável de ambiente `ALTITUDE_ENCRYPTION_KEY`
   - Permissões de pastas (755 para pastas, 644 para arquivos)

3. **Após primeiro deploy:**
   - Acesse o site para auto-instalação
   - Login padrão: `admin@teste.com` / `admin123`
   - **Altere a senha imediatamente**

## Checklist Pós-Deploy

- [ ] Site está online e acessível
- [ ] Login funciona corretamente
- [ ] Banco de dados foi criado (auto-install)
- [ ] Funcionalidades críticas funcionam
- [ ] `version.json` foi atualizado no servidor

## Adicionar Novo Servidor

Para adicionar um 4º servidor ou mais:

1. Edite `.github/workflows/ftp-deploy.yml`
2. Na seção `matrix.include`, adicione:
   ```yaml
   - server_id: "4"
     server_name: "Servidor Quaternário"
     enabled: true
   ```
3. Adicione o case no step "Verificar se servidor está configurado"
4. Adicione o case no step "Definir configuração do servidor"
5. Adicione um novo step "Deploy via FTP - Servidor 4"
6. Configure os secrets e variables no GitHub

---

**Status:** ✅ Deploy automático multi-servidor configurado!
