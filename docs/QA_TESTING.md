# QA Testing Script - Altitude Sistema de Estudo

Script de testes de Quality Assurance para validar todas as funcionalidades da plataforma.

**Ambiente de Testes:** https://altitude-dev.zaiden.eng.br (develop)
**Credenciais Admin:** admin@teste.com / admin123
**Data:** ___/___/______
**Testador:** ___________________

---

## Como usar este documento

- Marque cada item com `[x]` quando o teste passar
- Se falhar, anote o problema na coluna "Observações"
- Prioridade: `P0` = Blocker, `P1` = Crítico, `P2` = Importante, `P3` = Menor

---

## 1. Autenticação e Sessão

### 1.1 Login (/login.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 1.1.1 | Página de login carrega sem erros | P0 | [ ] | |
| 1.1.2 | Login com credenciais válidas redireciona para /home.php | P0 | [ ] | |
| 1.1.3 | Login com email inválido mostra mensagem de erro | P0 | [ ] | |
| 1.1.4 | Login com senha incorreta mostra mensagem de erro | P0 | [ ] | |
| 1.1.5 | Login com campos vazios mostra mensagem de erro | P1 | [ ] | |
| 1.1.6 | Token CSRF presente no formulário | P1 | [ ] | |
| 1.1.7 | Login com admin padrão (admin123) redireciona para /alterar_senha.php?required=1 | P1 | [ ] | |

### 1.2 Registo (/register.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 1.2.1 | Página de registo carrega sem erros | P0 | [ ] | |
| 1.2.2 | Criar conta com dados válidos | P0 | [ ] | |
| 1.2.3 | Registo com email já existente mostra erro | P0 | [ ] | |
| 1.2.4 | Registo com senhas diferentes mostra erro | P1 | [ ] | |
| 1.2.5 | Registo com senha menor que 6 caracteres mostra erro | P1 | [ ] | |
| 1.2.6 | Registo com campos vazios mostra erro | P1 | [ ] | |
| 1.2.7 | Token CSRF presente no formulário | P1 | [ ] | |
| 1.2.8 | Após registo, consegue fazer login com a nova conta | P0 | [ ] | |

### 1.3 Alterar Senha (/alterar_senha.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 1.3.1 | Página carrega com parâmetro ?required=1 | P0 | [ ] | |
| 1.3.2 | Alteração com senha atual correta funciona | P0 | [ ] | |
| 1.3.3 | Senha atual incorreta mostra erro | P1 | [ ] | |
| 1.3.4 | Nova senha menor que 8 caracteres é rejeitada | P1 | [ ] | |
| 1.3.5 | Nova senha igual à atual é rejeitada | P2 | [ ] | |
| 1.3.6 | Nova senha "admin123" é rejeitada | P2 | [ ] | |
| 1.3.7 | Confirmação de senha diferente mostra erro | P1 | [ ] | |

### 1.4 Logout (/logout.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 1.4.1 | Logout limpa sessão e redireciona para index | P0 | [ ] | |
| 1.4.2 | Após logout, aceder /home.php redireciona para login | P0 | [ ] | |
| 1.4.3 | Botão voltar do browser após logout não acede áreas protegidas | P2 | [ ] | |

### 1.5 Controlo de Acesso

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 1.5.1 | Utilizador anónimo não acede /home.php (redireciona para login) | P0 | [ ] | |
| 1.5.2 | Utilizador anónimo não acede /admin/*.php | P0 | [ ] | |
| 1.5.3 | Utilizador não-admin não acede /admin/*.php | P0 | [ ] | |
| 1.5.4 | Utilizador anónimo pode aceder /index.php | P0 | [ ] | |
| 1.5.5 | Utilizador anónimo pode aceder /login.php e /register.php | P0 | [ ] | |

---

## 2. Página Inicial e Dashboard

### 2.1 Página Pública (/index.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 2.1.1 | Página carrega sem erros (utilizador anónimo) | P0 | [ ] | |
| 2.1.2 | Links "Entrar" e "Cadastrar" funcionam | P1 | [ ] | |
| 2.1.3 | Estatísticas gerais são exibidas | P2 | [ ] | |
| 2.1.4 | Utilizador logado é redirecionado para /home.php | P1 | [ ] | |
| 2.1.5 | Favicon aparece no browser | P3 | [ ] | |

### 2.2 Dashboard (/home.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 2.2.1 | Página carrega com estatísticas do utilizador | P0 | [ ] | |
| 2.2.2 | Nome do utilizador exibido corretamente | P1 | [ ] | |
| 2.2.3 | Cards de estatísticas (aulas concluídas, cursos, tempo, streak) | P1 | [ ] | |
| 2.2.4 | Links de navegação na sidebar funcionam | P1 | [ ] | |
| 2.2.5 | Sidebar menu admin visível apenas para admins | P1 | [ ] | |

---

## 3. Cursos

### 3.1 Lista de Cursos (/cursos.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 3.1.1 | Página carrega com todos os cursos ativos | P0 | [ ] | |
| 3.1.2 | Cursos agrupados por categoria | P2 | [ ] | |
| 3.1.3 | Barra de progresso exibida em cada curso | P1 | [ ] | |
| 3.1.4 | Botão "Continuar" / "Iniciar" funciona | P0 | [ ] | |
| 3.1.5 | Favoritar curso (ícone de coração) funciona | P2 | [ ] | |
| 3.1.6 | Secção de favoritos exibe cursos favoritados | P2 | [ ] | |
| 3.1.7 | Arquivar curso funciona | P2 | [ ] | |
| 3.1.8 | Contador de arquivados atualiza | P3 | [ ] | |

### 3.2 Detalhe do Curso (/curso.php?id=ID)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 3.2.1 | Página carrega com dados do curso | P0 | [ ] | |
| 3.2.2 | Lista de aulas exibida com status de conclusão | P0 | [ ] | |
| 3.2.3 | Barra de progresso do curso correta | P1 | [ ] | |
| 3.2.4 | Clicar numa aula navega para /aula.php | P0 | [ ] | |
| 3.2.5 | ID de curso inválido redireciona para /home.php | P2 | [ ] | |
| 3.2.6 | Imagem de capa do curso exibida (se existir) | P2 | [ ] | |

---

## 4. Aulas

### 4.1 Visualização de Aula (/aula.php?id=ID)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 4.1.1 | Página carrega sem erros | P0 | [ ] | |
| 4.1.2 | Vídeo do YouTube carrega no iframe | P0 | [ ] | |
| 4.1.3 | Vídeo do Vimeo carrega no iframe | P1 | [ ] | |
| 4.1.4 | Vídeo do OneDrive carrega no iframe | P1 | [ ] | |
| 4.1.5 | Vídeo do Dropbox carrega na tag video | P1 | [ ] | |
| 4.1.6 | Mensagem "Vídeo não disponível" quando URL inválida | P2 | [ ] | |
| 4.1.7 | Breadcrumb exibido corretamente | P3 | [ ] | |
| 4.1.8 | ID de aula inválido redireciona para /home.php | P2 | [ ] | |

### 4.2 Modo Teatro

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 4.2.1 | Botão modo teatro expande o vídeo | P2 | [ ] | |
| 4.2.2 | Tecla ESC fecha o modo teatro | P2 | [ ] | |
| 4.2.3 | Clicar fora do vídeo fecha o modo teatro | P2 | [ ] | |
| 4.2.4 | Ícone alterna entre expandir/comprimir | P3 | [ ] | |

### 4.3 Progresso de Aula

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 4.3.1 | Botão "Marcar como Concluída" funciona (AJAX) | P0 | [ ] | |
| 4.3.2 | Botão alterna para "Marcar como Não Concluída" | P1 | [ ] | |
| 4.3.3 | Progresso do curso atualiza na sidebar | P1 | [ ] | |
| 4.3.4 | Aula ativa destacada na lista de aulas | P2 | [ ] | |
| 4.3.5 | Aulas concluídas exibem ícone de check | P2 | [ ] | |

### 4.4 Anotações

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 4.4.1 | Botão "Adicionar" anotação abre o formulário | P1 | [ ] | |
| 4.4.2 | Salvar anotação via AJAX funciona | P0 | [ ] | |
| 4.4.3 | Anotação existente é exibida formatada (Markdown) | P1 | [ ] | |
| 4.4.4 | Botão "Editar" abre o formulário com conteúdo existente | P1 | [ ] | |
| 4.4.5 | Botão "Cancelar" fecha o formulário | P2 | [ ] | |
| 4.4.6 | Anotação vazia não é salva (mostra erro) | P2 | [ ] | |

### 4.5 Navegação

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 4.5.1 | Botão "Próxima" aula funciona | P1 | [ ] | |
| 4.5.2 | Botão "Anterior" aula funciona | P1 | [ ] | |
| 4.5.3 | Primeira aula não exibe botão "Anterior" | P2 | [ ] | |
| 4.5.4 | Última aula não exibe botão "Próxima" | P2 | [ ] | |
| 4.5.5 | Botão "Compartilhar" copia link para clipboard | P3 | [ ] | |
| 4.5.6 | Link "Abrir vídeo original" abre URL em nova aba | P3 | [ ] | |

### 4.6 Materiais Complementares

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 4.6.1 | Materiais exibidos com ícones por tipo (PDF, DOC, etc.) | P2 | [ ] | |
| 4.6.2 | Botão "Baixar" abre o material em nova aba | P1 | [ ] | |
| 4.6.3 | Secção oculta quando não há materiais | P3 | [ ] | |

---

## 5. Simulados (Quizzes)

### 5.1 Lista de Simulados (/simulados.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 5.1.1 | Página carrega com lista de simulados | P0 | [ ] | |
| 5.1.2 | Cards de estatísticas exibidos (Total, Média, Disponíveis, Última Nota) | P1 | [ ] | |
| 5.1.3 | Cada simulado exibe disciplina, nº questões, tempo limite | P1 | [ ] | |
| 5.1.4 | Botão "Iniciar Simulado" funciona | P0 | [ ] | |
| 5.1.5 | Botão "Refazer" exibido para simulados já realizados | P1 | [ ] | |
| 5.1.6 | Histórico de tentativas abre/fecha | P2 | [ ] | |
| 5.1.7 | Badge com nº de tentativas exibido | P2 | [ ] | |

### 5.2 Realizar Simulado (/realizar_simulado.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 5.2.1 | Questões carregam corretamente | P0 | [ ] | |
| 5.2.2 | Selecionar alternativa marca como selecionada | P0 | [ ] | |
| 5.2.3 | Botão "Responder" submete a resposta | P0 | [ ] | |
| 5.2.4 | Resposta correta exibe feedback verde | P0 | [ ] | |
| 5.2.5 | Resposta incorreta exibe feedback vermelho | P0 | [ ] | |
| 5.2.6 | Navegação entre questões (botões numerados) funciona | P1 | [ ] | |
| 5.2.7 | Botões "Anterior" e "Próxima" funcionam | P1 | [ ] | |
| 5.2.8 | Barra de progresso atualiza | P2 | [ ] | |
| 5.2.9 | Texto de apoio abre em modal | P2 | [ ] | |
| 5.2.10 | Timer decrementa (se tempo_limite > 0) | P1 | [ ] | |
| 5.2.11 | Timer expirado mostra modal "Tempo Esgotado" e finaliza | P1 | [ ] | |
| 5.2.12 | Botão "Finalizar" abre modal de confirmação | P1 | [ ] | |
| 5.2.13 | Após finalizar, modal de resultado exibe nota | P0 | [ ] | |
| 5.2.14 | Mensagem de performance varia conforme nota (>=70%, >=50%, <50%) | P2 | [ ] | |

### 5.3 Análise IA de Questão

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 5.3.1 | Análise IA é gerada para respostas incorretas | P1 | [ ] | |
| 5.3.2 | Análise exibida em formato Markdown | P2 | [ ] | |
| 5.3.3 | Loading indicator exibido durante análise | P2 | [ ] | |
| 5.3.4 | Erro tratado quando IA não está configurada | P1 | [ ] | |

### 5.4 Resultado do Simulado (/resultado_simulado.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 5.4.1 | Página de resultados carrega | P0 | [ ] | |
| 5.4.2 | Nota exibida corretamente | P0 | [ ] | |
| 5.4.3 | Revisão questão-a-questão disponível | P1 | [ ] | |
| 5.4.4 | Análise IA exibida para cada questão errada | P2 | [ ] | |
| 5.4.5 | Botão voltar para simulados funciona | P2 | [ ] | |

---

## 6. Certificados (/certificados.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 6.1 | Página carrega sem erros | P0 | [ ] | |
| 6.2 | Certificados da plataforma exibidos para cursos 100% concluídos | P0 | [ ] | |
| 6.3 | Botão "Baixar Certificado" gera PDF | P0 | [ ] | |
| 6.4 | PDF contém nome do aluno, curso e data corretos | P1 | [ ] | |
| 6.5 | Formulário "Adicionar Certificado Externo" abre/fecha | P1 | [ ] | |
| 6.6 | Upload de certificado externo (PDF, JPG, PNG) funciona | P1 | [ ] | |
| 6.7 | Upload de arquivo > 5MB é rejeitado | P2 | [ ] | |
| 6.8 | Upload de formato inválido é rejeitado | P2 | [ ] | |
| 6.9 | Certificados externos organizados por categoria | P2 | [ ] | |
| 6.10 | Excluir certificado externo com confirmação | P2 | [ ] | |

---

## 7. Secção de Inglês (/ingles/)

### 7.1 Lições de Inglês (/ingles/licoes.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 7.1.1 | Página carrega sem erros | P0 | [ ] | |
| 7.1.2 | Formulário de geração de lição exibido | P1 | [ ] | |
| 7.1.3 | Gerar lição com IA funciona (quando configurada) | P1 | [ ] | |
| 7.1.4 | Erro exibido quando IA não configurada | P2 | [ ] | |
| 7.1.5 | Lista de lições anteriores exibida | P2 | [ ] | |

### 7.2 Realizar Lição (/ingles/realizar_licao.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 7.2.1 | Lição carrega com questões | P0 | [ ] | |
| 7.2.2 | Diferentes tipos de questão renderizam corretamente | P1 | [ ] | |
| 7.2.3 | Submeter respostas funciona | P0 | [ ] | |
| 7.2.4 | Feedback de IA recebido | P1 | [ ] | |

### 7.3 Diário de Inglês (/ingles/diario.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 7.3.1 | Criar nova entrada no diário | P1 | [ ] | |
| 7.3.2 | Editar entrada existente | P2 | [ ] | |
| 7.3.3 | Exportar diário funciona | P2 | [ ] | |

### 7.4 Anotações de Inglês (/ingles/anotacoes.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 7.4.1 | Criar anotação | P1 | [ ] | |
| 7.4.2 | Editar anotação | P2 | [ ] | |
| 7.4.3 | Excluir anotação | P2 | [ ] | |

---

## 8. Pesquisa

### 8.1 Autocomplete (Header)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 8.1.1 | Autocomplete aparece ao digitar 2+ caracteres | P1 | [ ] | |
| 8.1.2 | Resultados mostram cursos e aulas | P1 | [ ] | |
| 8.1.3 | Clicar num resultado navega para a página | P1 | [ ] | |
| 8.1.4 | Navegação com setas (up/down) funciona | P3 | [ ] | |
| 8.1.5 | Enter submete o formulário de pesquisa | P2 | [ ] | |
| 8.1.6 | ESC fecha o autocomplete | P3 | [ ] | |

### 8.2 Página de Pesquisa (/pesquisa.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 8.2.1 | Pesquisa retorna cursos e aulas correspondentes | P1 | [ ] | |
| 8.2.2 | Resultados mostram progresso | P2 | [ ] | |
| 8.2.3 | Pesquisa vazia mostra placeholder | P3 | [ ] | |
| 8.2.4 | "Nenhum resultado" quando não há correspondência | P2 | [ ] | |

---

## 9. Estatísticas (/estatisticas.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 9.1 | Página carrega sem erros | P0 | [ ] | |
| 9.2 | Aulas concluídas exibido corretamente | P1 | [ ] | |
| 9.3 | Cursos ativos exibido corretamente | P1 | [ ] | |
| 9.4 | Tempo estudado calculado | P2 | [ ] | |
| 9.5 | Progresso por categoria exibido | P2 | [ ] | |
| 9.6 | Atividade recente listada em ordem | P2 | [ ] | |

---

## 10. Timer Pomodoro

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 10.1 | Botão do Pomodoro abre o modal | P2 | [ ] | |
| 10.2 | Botão "Iniciar" inicia contagem regressiva (25 min) | P2 | [ ] | |
| 10.3 | Botão "Pausar" pausa o timer | P2 | [ ] | |
| 10.4 | Botão "Reset" reinicia o timer | P2 | [ ] | |
| 10.5 | Modos: Foco (25min), Pausa Curta (5min), Pausa Longa (15min) | P2 | [ ] | |
| 10.6 | Fechar modal minimiza o timer (barra inferior) | P3 | [ ] | |
| 10.7 | Timer persiste entre páginas (localStorage) | P3 | [ ] | |
| 10.8 | Notificação sonora ao terminar | P3 | [ ] | |
| 10.9 | Notificação do browser ao terminar | P3 | [ ] | |
| 10.10 | Ciclo automático: Foco → Pausa Curta (4x) → Pausa Longa | P3 | [ ] | |

---

## 11. Administração

### 11.1 Gestão de Utilizadores (/admin/usuarios.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.1.1 | Página carrega com lista de utilizadores | P0 | [ ] | |
| 11.1.2 | Criar utilizador com todos os campos | P0 | [ ] | |
| 11.1.3 | Senha mínima de 12 caracteres exigida | P1 | [ ] | |
| 11.1.4 | Email duplicado rejeitado | P1 | [ ] | |
| 11.1.5 | Tornar utilizador admin funciona | P1 | [ ] | |
| 11.1.6 | Remover privilégio admin funciona | P1 | [ ] | |
| 11.1.7 | Não pode alterar o próprio status admin | P1 | [ ] | |
| 11.1.8 | Excluir utilizador (soft delete) funciona | P1 | [ ] | |
| 11.1.9 | Não pode excluir a si mesmo | P1 | [ ] | |

### 11.2 Gestão de Categorias (/admin/categorias.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.2.1 | Listar categorias | P0 | [ ] | |
| 11.2.2 | Criar categoria | P0 | [ ] | |
| 11.2.3 | Editar categoria | P1 | [ ] | |
| 11.2.4 | Excluir categoria (soft delete) | P1 | [ ] | |

### 11.3 Gestão de Cursos (/admin/cursos.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.3.1 | Listar cursos com categoria | P0 | [ ] | |
| 11.3.2 | Criar curso com título e categoria | P0 | [ ] | |
| 11.3.3 | Upload de imagem de capa (JPEG, PNG, WebP) | P1 | [ ] | |
| 11.3.4 | Upload de formato inválido rejeitado | P1 | [ ] | |
| 11.3.5 | Upload de arquivo > 5MB rejeitado | P1 | [ ] | |
| 11.3.6 | Editar curso (título, descrição, categoria, imagem) | P0 | [ ] | |
| 11.3.7 | Remover imagem de capa ao editar | P2 | [ ] | |
| 11.3.8 | Substituir imagem de capa | P2 | [ ] | |
| 11.3.9 | Excluir curso (soft delete) com confirmação | P0 | [ ] | |
| 11.3.10 | Token CSRF validado em todas as operações | P1 | [ ] | |

### 11.4 Gestão de Aulas (/admin/aulas.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.4.1 | Listar aulas com filtro por curso | P0 | [ ] | |
| 11.4.2 | Criar aula com título, vídeo URL e curso | P0 | [ ] | |
| 11.4.3 | URL do YouTube é convertida para embed | P1 | [ ] | |
| 11.4.4 | URL do Vimeo é convertida para embed | P2 | [ ] | |
| 11.4.5 | Editar aula | P0 | [ ] | |
| 11.4.6 | Excluir aula (soft delete) com confirmação | P0 | [ ] | |
| 11.4.7 | Definir duração e ordem da aula | P2 | [ ] | |
| 11.4.8 | Adicionar materiais complementares | P2 | [ ] | |
| 11.4.9 | Token CSRF validado em todas as operações | P1 | [ ] | |

### 11.5 Gestão de Simulados (/admin_simulados.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.5.1 | Listar simulados | P0 | [ ] | |
| 11.5.2 | Criar simulado (título, disciplina, tempo limite) | P0 | [ ] | |
| 11.5.3 | Adicionar questões (alternativas A-E, resposta correta) | P0 | [ ] | |
| 11.5.4 | Adicionar texto de apoio à questão | P2 | [ ] | |
| 11.5.5 | Editar simulado | P1 | [ ] | |
| 11.5.6 | Excluir simulado | P1 | [ ] | |
| 11.5.7 | Editar questões | P1 | [ ] | |
| 11.5.8 | Excluir questões | P1 | [ ] | |

### 11.6 Configurações de IA (/admin/configuracoes_ia.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.6.1 | Página carrega com configurações atuais | P0 | [ ] | |
| 11.6.2 | Selecionar provedor (Gemini, OpenAI, Groq) | P1 | [ ] | |
| 11.6.3 | Salvar chave de API (encriptada) | P0 | [ ] | |
| 11.6.4 | Alterar modelo funciona | P1 | [ ] | |
| 11.6.5 | Ajustar temperatura (0-1) | P2 | [ ] | |
| 11.6.6 | Salvar chave de API do YouTube | P2 | [ ] | |
| 11.6.7 | Token CSRF validado | P1 | [ ] | |

### 11.7 Importação YouTube (/admin/aulas.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.7.1 | Botão de importar playlist exibido | P1 | [ ] | |
| 11.7.2 | Importar playlist válida cria aulas | P1 | [ ] | |
| 11.7.3 | URL inválida mostra erro | P2 | [ ] | |
| 11.7.4 | Títulos e URLs dos vídeos importados corretamente | P1 | [ ] | |

### 11.8 Backup de Base de Dados (/admin/database.php)

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 11.8.1 | Página de backup carrega | P0 | [ ] | |
| 11.8.2 | Download de backup funciona | P0 | [ ] | |
| 11.8.3 | Arquivo de backup é válido (SQLite) | P1 | [ ] | |
| 11.8.4 | Restaurar backup com confirmação | P0 | [ ] | |
| 11.8.5 | Formato de arquivo inválido é rejeitado | P1 | [ ] | |
| 11.8.6 | Token CSRF validado no download e restauro | P1 | [ ] | |

---

## 12. Segurança

### 12.1 CSRF Protection

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 12.1.1 | Todos os formulários POST contêm token CSRF | P0 | [ ] | |
| 12.1.2 | Submissão sem token CSRF é rejeitada | P0 | [ ] | |
| 12.1.3 | Token CSRF inválido é rejeitado | P0 | [ ] | |
| 12.1.4 | Meta tag CSRF presente para AJAX | P1 | [ ] | |

### 12.2 Content Security Policy

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 12.2.1 | Headers CSP presentes na resposta HTTP | P1 | [ ] | |
| 12.2.2 | YouTube embeds funcionam (frame-src) | P0 | [ ] | |
| 12.2.3 | Vimeo embeds funcionam (frame-src) | P1 | [ ] | |
| 12.2.4 | OneDrive embeds funcionam (frame-src) | P1 | [ ] | |
| 12.2.5 | Scripts Tailwind/FontAwesome carregam (script-src, style-src) | P0 | [ ] | |

### 12.3 Validação de Input

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 12.3.1 | XSS: inserir `<script>alert(1)</script>` em campos de texto | P0 | [ ] | |
| 12.3.2 | SQL Injection: inserir `' OR 1=1 --` em campos de login | P0 | [ ] | |
| 12.3.3 | Upload de ficheiro PHP disfarçado rejeitado | P0 | [ ] | |
| 12.3.4 | Campos numéricos rejeitam texto | P2 | [ ] | |

### 12.4 Headers de Segurança

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 12.4.1 | X-Content-Type-Options: nosniff | P1 | [ ] | |
| 12.4.2 | X-Frame-Options: DENY | P1 | [ ] | |
| 12.4.3 | Referrer-Policy presente | P2 | [ ] | |
| 12.4.4 | HSTS header (em HTTPS) | P2 | [ ] | |
| 12.4.5 | Permissions-Policy presente | P3 | [ ] | |

---

## 13. Responsividade e UI

### 13.1 Layout Responsivo

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 13.1.1 | Desktop (1920x1080): layout correto com sidebar | P0 | [ ] | |
| 13.1.2 | Tablet (768px): sidebar esconde, menu mobile funciona | P1 | [ ] | |
| 13.1.3 | Mobile (375px): conteúdo legível sem scroll horizontal | P1 | [ ] | |
| 13.1.4 | Botão hamburger abre sidebar no mobile | P1 | [ ] | |
| 13.1.5 | Backdrop fecha sidebar no mobile | P2 | [ ] | |

### 13.2 Elementos de UI

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 13.2.1 | Favicon exibido no browser | P3 | [ ] | |
| 13.2.2 | Dropdown do utilizador (header) abre/fecha | P2 | [ ] | |
| 13.2.3 | Dropdown admin na sidebar abre/fecha | P2 | [ ] | |
| 13.2.4 | Estado do dropdown admin persiste (localStorage) | P3 | [ ] | |
| 13.2.5 | Mensagens de sucesso/erro exibidas corretamente | P1 | [ ] | |
| 13.2.6 | Notificações toast aparecem e desaparecem (3s) | P2 | [ ] | |
| 13.2.7 | Versão do sistema exibida no rodapé da sidebar | P3 | [ ] | |

---

## 14. APIs (Testes Funcionais)

| # | Endpoint | Método | Teste | Status | Observações |
|---|----------|--------|-------|--------|-------------|
| 14.1 | /api/progresso.php | POST | Marcar aula concluída | [ ] | |
| 14.2 | /api/progresso.php | POST | Desmarcar aula concluída | [ ] | |
| 14.3 | /api/anotacoes.php | POST | Salvar anotação | [ ] | |
| 14.4 | /api/simulados.php?action=listar | GET | Listar simulados | [ ] | |
| 14.5 | /api/simulados.php?action=iniciar | POST | Iniciar tentativa | [ ] | |
| 14.6 | /api/simulados.php?action=responder | POST | Responder questão | [ ] | |
| 14.7 | /api/simulados.php?action=finalizar | POST | Finalizar simulado | [ ] | |
| 14.8 | /api/analise_questao_ia.php | POST | Análise IA de questão | [ ] | |
| 14.9 | /api/autocomplete.php?q=QUERY | GET | Autocomplete de pesquisa | [ ] | |
| 14.10 | /api/favoritar_curso.php | POST | Favoritar curso | [ ] | |
| 14.11 | /api/arquivar_curso.php | POST | Arquivar curso | [ ] | |
| 14.12 | /api/gerar_certificado.php | GET | Gerar certificado PDF | [ ] | |
| 14.13 | /api/importar_playlist_youtube.php | POST | Importar playlist | [ ] | |

---

## 15. Integração IA

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 15.1 | Gemini (gemini-2.5-flash): análise de questão funciona | P1 | [ ] | |
| 15.2 | OpenAI (gpt-4o-mini): análise de questão funciona | P2 | [ ] | |
| 15.3 | Groq (llama-3.1-8b-instant): análise de questão funciona | P2 | [ ] | |
| 15.4 | Troca de provedor funciona sem reiniciar | P2 | [ ] | |
| 15.5 | Erro exibido quando chave de API inválida | P1 | [ ] | |
| 15.6 | Rate limiting funciona (evita spam de requisições) | P2 | [ ] | |
| 15.7 | Geração de lição de inglês via IA | P2 | [ ] | |
| 15.8 | Revisão de texto em inglês via IA | P2 | [ ] | |

---

## 16. Base de Dados

| # | Teste | Prioridade | Status | Observações |
|---|-------|-----------|--------|-------------|
| 16.1 | Auto-instalação funciona em primeira execução | P0 | [ ] | |
| 16.2 | Todas as tabelas são criadas | P0 | [ ] | |
| 16.3 | Dados iniciais inseridos (admin, categorias, cursos de exemplo) | P1 | [ ] | |
| 16.4 | SQLite: queries com TRUE/FALSE funcionam | P1 | [ ] | |
| 16.5 | Prepared statements usados em todas as queries | P0 | [ ] | |
| 16.6 | Transações com rollback em caso de erro | P1 | [ ] | |

---

## Resumo da Execução

| Secção | Total Testes | Passou | Falhou | Bloqueado |
|--------|-------------|--------|--------|-----------|
| 1. Autenticação | 22 | | | |
| 2. Página Inicial | 10 | | | |
| 3. Cursos | 14 | | | |
| 4. Aulas | 22 | | | |
| 5. Simulados | 22 | | | |
| 6. Certificados | 10 | | | |
| 7. Inglês | 12 | | | |
| 8. Pesquisa | 10 | | | |
| 9. Estatísticas | 6 | | | |
| 10. Pomodoro | 10 | | | |
| 11. Administração | 40 | | | |
| 12. Segurança | 14 | | | |
| 13. Responsividade | 12 | | | |
| 14. APIs | 13 | | | |
| 15. Integração IA | 8 | | | |
| 16. Base de Dados | 6 | | | |
| **TOTAL** | **231** | | | |

---

## Bugs Encontrados

| # | Severidade | Secção | Descrição | Passos para Reproduzir | Estado |
|---|-----------|--------|-----------|----------------------|--------|
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |
