# Plano de Implementação: Sistema de Revisão Espaçada Inteligente

## Visão Geral

Implementar sistema completo de Spaced Repetition (Revisão Espaçada) no Altitude LMS com:
- Criação manual e automática (IA) de flashcards
- Algoritmo SM-2 (SuperMemo-2) adaptativo
- Interface de revisão interativa
- Estatísticas e heatmap de progresso

**MVP Scope**: Flashcards de notas + Estatísticas (sem flashcards de erros de simulado e sugestões IA de timing)

---

## Arquitetura do Sistema

### 6 Novas Tabelas (PostgreSQL + SQLite compatível)

1. **flashcard_decks** - Baralhos/decks personalizados do usuário (Inglês, AZ-900, Python, etc.)
2. **flashcards** - Armazena os cartões (pergunta/resposta) vinculados a um deck
3. **flashcard_revisoes** - Agenda SR e histórico (algoritmo SM-2)
4. **flashcard_tentativas** - Sessões de revisão
5. **flashcard_respostas** - Respostas individuais com rating 0-5
6. **revisao_sugestoes_ia** - Sugestões de IA (futura, criar tabela vazia)

### 3 Novas Páginas

1. **flashcards.php** - Gerenciamento e listagem
2. **revisar_flashcards.php** - Interface de revisão
3. **estatisticas_revisao.php** - Analytics e heatmap

### 2 Novos Endpoints API

1. **api/flashcards.php** - CRUD e geração IA
2. **api/revisao.php** - Sessões e algoritmo SM-2

---

## Implementação Passo a Passo

### FASE 1: Database Schema

**Arquivo**: `includes/auto_install.php`

**Ações**:
1. Adicionar 6 tabelas no método `createTables()` (após linha 313)
2. Usar condicional `$db->isPostgreSQL()` para auto-increment
3. Usar `$db->getBoolTrue()` para valores booleanos
4. Criar índices para performance

**Tabelas**:

```sql
-- flashcard_decks: baralhos personalizados (nome, descrição, cor, ícone)
-- flashcards: pergunta, resposta, dica, deck_id (FK), fonte (nota/manual/ia), tags
-- flashcard_revisoes: SM-2 fields (intervalo_dias, repeticoes, facilidade, proxima_revisao)
-- flashcard_tentativas: sessão de revisão (deck_id, data_inicio, data_fim, stats)
-- flashcard_respostas: rating 0-5, tempo_resposta, antes/depois SR values
-- revisao_sugestoes_ia: tabela vazia para feature futura
```

**Estrutura detalhada - flashcard_decks**:

```sql
CREATE TABLE IF NOT EXISTS flashcard_decks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,

    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    cor VARCHAR(20) DEFAULT 'blue',  -- blue, green, purple, red, yellow, pink
    icone VARCHAR(50) DEFAULT 'fa-layer-group',  -- FontAwesome class

    -- Opcional: vincular a curso específico
    curso_id INTEGER,

    -- Stats (calculadas)
    total_cards INTEGER DEFAULT 0,
    cards_novos INTEGER DEFAULT 0,
    cards_para_revisar INTEGER DEFAULT 0,

    ativo INTEGER DEFAULT 1,
    ordem INTEGER DEFAULT 0,  -- Para ordenação customizada

    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_decks_usuario ON flashcard_decks(usuario_id);
CREATE INDEX IF NOT EXISTS idx_decks_curso ON flashcard_decks(curso_id);
```

**Atualização na tabela flashcards**:
- Adicionar campo `deck_id INTEGER NOT NULL` com FK para flashcard_decks
- Remover campos `aula_id` e `curso_id` (deck já organiza por assunto)
- Manter `fonte` para rastreabilidade (se veio de nota, manual, IA)

**Campos-chave SM-2**:
- `intervalo_dias` - Dias até próxima revisão (1, 6, 15, 37...)
- `repeticoes` - Número de acertos consecutivos
- `facilidade` - Fator de facilidade (1.3-2.5, default 2.5)
- `proxima_revisao` - Data calculada para próxima revisão

---

### FASE 2: API Core - Flashcards

**Arquivo**: `api/flashcards.php` (novo)

**Padrão**: Seguir estrutura de `api/simulados.php`

**Adicionar endpoint de Decks**:

0. **listar_decks** (GET)
   - Lista todos os decks do usuário
   - Retorna: id, nome, descrição, cor, icone, total_cards, cards_para_revisar
   - Order by: ordem ASC, nome ASC

0b. **criar_deck** (POST)
   - Params: nome, descricao, cor, icone, curso_id (opcional)
   - Cria novo deck
   - Retorna deck_id

0c. **editar_deck** (POST)
   - Params: deck_id, nome, descricao, cor, icone
   - Atualiza deck
   - Verifica ownership

0d. **excluir_deck** (POST)
   - Soft delete (ativo = 0)
   - Pergunta ao usuário o que fazer com flashcards: mover para outro deck ou excluir

**Actions implementar (Flashcards)**:

1. **listar** (GET)
   - Lista flashcards do usuário
   - **Filtros: deck_id (OBRIGATÓRIO ou null para "sem deck"), fonte, ativo**
   - Order by: data_atualizacao DESC
   - Joins com flashcard_decks para contexto

2. **criar** (POST)
   - Criação manual
   - **Params: deck_id (OBRIGATÓRIO), pergunta, resposta, dica, tags**
   - Valida ownership do deck
   - Cria registro em flashcards + flashcard_revisoes (estado inicial)
   - Incrementa total_cards do deck

3. **editar** (POST)
   - Atualiza pergunta/resposta/dica
   - Verifica ownership (usuario_id)
   - Atualiza data_atualizacao

4. **excluir** (POST)
   - Soft delete (ativo = 0)
   - Verifica ownership

5. **gerar_de_nota** (POST) - **IA Integration**
   - **Recebe: aula_id, deck_id**
   - Busca anotação do usuário
   - Chama `AIHelper->generateFlashcards($conteudo)`
   - Retorna JSON com preview dos flashcards
   - Frontend mostra modal de preview antes de salvar
   - Ao salvar, adiciona ao deck especificado

6. **cards_devidos** (GET)
   - **Params: deck_id (opcional - se não enviado, todos os decks)**
   - Busca em flashcard_revisoes WHERE proxima_revisao <= CURRENT_DATE
   - Joins com flashcards para retornar pergunta/resposta
   - Usado para badge "X cards para revisar"

7. **estatisticas** (GET)
   - **Por deck ou global**
   - Total cards, cards por nível (novo/aprendendo/jovem/maduro)
   - Cards devidos hoje
   - Taxa de acertos (últimos 7 dias)
   - Streak (dias consecutivos revisando)

**Security**: Todos os queries filtram por `usuario_id = $_SESSION['user_id']`

---

### FASE 3: API Core - Revisão

**Arquivo**: `api/revisao.php` (novo)

**Actions implementar**:

1. **iniciar_sessao** (POST)
   - **Params: deck_id (opcional - se não enviado, revisa cards de todos os decks)**
   - Cria flashcard_tentativas (sessao_hash = uniqid(), deck_id)
   - Retorna sessao_id e lista de cards devidos
   - Embaralha ordem dos cards

2. **responder** (POST)
   - Params: sessao_id, flashcard_id, qualidade (0-5)
   - Chama função `calcularProximaRevisao()` com SM-2
   - Atualiza flashcard_revisoes com novos valores
   - Cria registro em flashcard_respostas
   - Retorna novos valores (próxima revisão, intervalo)

3. **finalizar_sessao** (POST)
   - Atualiza flashcard_tentativas (finalizado=1, data_fim, stats)
   - Retorna resumo: cards_revisados, corretos, tempo_total

4. **sessoes_recentes** (GET)
   - Lista últimas 10 sessões
   - Mostra stats por sessão

5. **calendario_revisoes** (GET)
   - Retorna dados para heatmap (estilo GitHub)
   - Formato: {data: 'YYYY-MM-DD', count: N}
   - Últimos 90 dias

**Algoritmo SM-2** (função helper):

```php
function calcularProximaRevisao($qualidade, $dadosAtuais) {
    $facilidade = $dadosAtuais['facilidade'];
    $intervalo = $dadosAtuais['intervalo_dias'];
    $repeticoes = $dadosAtuais['repeticoes'];

    if ($qualidade >= 3) {
        // Acertou
        $repeticoes += 1;
        if ($repeticoes == 1) $intervalo = 1;
        elseif ($repeticoes == 2) $intervalo = 6;
        else $intervalo = round($intervalo * $facilidade);

        // Atualiza facilidade
        $facilidade += (0.1 - (5-$qualidade) * (0.08 + (5-$qualidade) * 0.02));
        $facilidade = max(1.3, $facilidade);
    } else {
        // Errou - reset
        $repeticoes = 0;
        $intervalo = 1;
    }

    return [
        'intervalo_dias' => $intervalo,
        'repeticoes' => $repeticoes,
        'facilidade' => round($facilidade, 2),
        'proxima_revisao' => date('Y-m-d', strtotime("+{$intervalo} days"))
    ];
}
```

---

### FASE 4: AI Helper Extension

**Arquivo**: `includes/ai_helper.php`

**Adicionar método**:

```php
public function generateFlashcards($noteContent, $maxCards = 10) {
    $systemPrompt = "Você é um especialista em criar flashcards eficazes para estudos usando técnicas de aprendizagem ativa.";

    $userPrompt = "Analise a seguinte nota de estudo e extraia até {$maxCards} flashcards:

NOTA:
{$noteContent}

REGRAS:
- Perguntas claras e específicas
- Respostas concisas mas completas (máx 200 caracteres)
- Foque em conceitos-chave e definições
- Varie tipos: definições, exemplos, aplicações

Retorne APENAS JSON válido:
{
  \"flashcards\": [
    {\"pergunta\": \"O que é X?\", \"resposta\": \"...\", \"dica\": \"pense em Y\"}
  ]
}";

    $result = $this->analyzeQuestion($userPrompt); // Reusa método existente

    // Parse JSON
    $json = json_decode($result, true);
    if (!$json || !isset($json['flashcards'])) {
        throw new Exception('IA retornou formato inválido');
    }

    return $json['flashcards'];
}
```

**Nota**: Gemini 2.5 Flash tem bom suporte a JSON, mas adicionar tratamento de erro robusto.

---

### FASE 5: UI - Gerenciamento de Flashcards

**Arquivo**: `flashcards.php` (novo)

**Layout**: Full layout com sidebar (usar `renderLayout()`)

**Seções**:

1. **Sidebar de Decks** (esquerda, 280px)
   - Botão "+ Novo Baralho" (destaque)
   - Lista de decks com:
     - Ícone colorido (personalizável)
     - Nome do deck
     - Badge com número de cards para revisar
     - Cards totais
   - Seleção ativa (destaca deck atual)
   - Opção "Todos os Baralhos" (mostra todos os cards)

2. **Header com estatísticas** (do deck selecionado)
   - Total de flashcards neste deck
   - Cards para revisar hoje (badge vermelho se > 0)
   - Cards aprendidos (nível maduro)
   - Taxa de retenção (%)

3. **Ações principais** (botões)
   - "Criar Flashcard Manual" (abre modal, pré-seleciona deck atual)
   - "Gerar de Notas com IA" (dropdown de aulas, escolhe deck destino)
   - "Revisar Este Baralho" (link para revisar_flashcards.php?deck_id=X)
   - "Editar Baralho" (nome, cor, ícone)

4. **Lista de flashcards** (tabela ou cards do deck selecionado)
   - Colunas: Pergunta (truncada), Fonte, Próxima Revisão, Nível, Ações
   - Filtros: Fonte, Nível
   - Busca: full-text em pergunta/resposta
   - Ações: Editar, Mover para outro deck, Excluir

5. **Modal de criação de deck**
   - Form: nome (input), descrição (textarea)
   - Seletor de cor (6 opções: blue, green, purple, red, yellow, pink)
   - Seletor de ícone (10 opções populares: book, graduation-cap, code, language, etc.)
   - Opcional: vincular a um curso
   - Submit via AJAX para api/flashcards.php?action=criar_deck

6. **Modal de criação manual de flashcard**
   - Select de deck (se não vier de deck específico)
   - Form: pergunta (textarea), resposta (textarea), dica (input)
   - Submit via AJAX para api/flashcards.php?action=criar

7. **Modal de geração IA**
   - Select de deck destino
   - Select de aula (apenas aulas com notas)
   - Botão "Gerar" → loading spinner
   - Preview dos flashcards gerados (lista editável)
   - Checkboxes para selecionar quais salvar
   - Botão "Salvar Selecionados"

**JavaScript**:
- AJAX para todas as operações
- Toast notifications (success/error)
- Confirmação antes de excluir

---

### FASE 6: UI - Sessão de Revisão

**Arquivo**: `revisar_flashcards.php` (novo)

**Layout**: Simple layout sem sidebar (usar `renderSimpleLayout()`) - modo focado

**URL**: `revisar_flashcards.php?deck_id=X` (opcional - sem deck_id revisa todos)

**Estrutura**:

1. **Header fixo** (topo)
   - Nome do deck sendo revisado (ou "Todos os Baralhos")
   - Progresso: "Card X de Y"
   - Timer da sessão
   - Botão "Finalizar Revisão"

2. **Card principal** (centro)
   - **Modo Pergunta**:
     - Pergunta em fonte grande (24px)
     - Dica (se existir, em cinza claro)
     - Botão "Mostrar Resposta" (destaque)

   - **Modo Resposta**:
     - Pergunta + Resposta mostradas
     - 6 botões de qualidade (0-5):
       - 0: "Esqueci totalmente" (vermelho escuro)
       - 1: "Errei, mas lembrei ao ver" (vermelho)
       - 2: "Errei, mas quase" (laranja)
       - 3: "Certo com dificuldade" (amarelo)
       - 4: "Certo com hesitação" (verde claro)
       - 5: "Certo facilmente" (verde escuro)
     - Cada botão mostra próximo intervalo: "Rever em X dias"

3. **Feedback visual**:
   - Animação de flip ao mostrar resposta
   - Após selecionar qualidade: toast com "Próxima revisão em X dias"
   - Progresso visual (barra)

4. **Fim da sessão**:
   - Modal com resumo:
     - Cards revisados
     - Tempo total
     - Distribuição de qualidade (gráfico pizza)
     - Botão "Ver Estatísticas" → estatisticas_revisao.php
     - Botão "Revisar Mais Cards" (se houver)

**JavaScript Flow**:
1. `iniciarSessao()` - POST api/revisao.php?action=iniciar_sessao
2. Carrega primeiro card
3. `mostrarResposta()` - toggle view
4. `selecionarQualidade(q)` - POST api/revisao.php?action=responder
5. Carrega próximo card
6. `finalizarSessao()` - POST api/revisao.php?action=finalizar_sessao

---

### FASE 7: UI - Estatísticas

**Arquivo**: `estatisticas_revisao.php` (novo)

**Layout**: Full layout com sidebar

**Seções**:

1. **Seletor de Deck** (dropdown)
   - "Todos os Baralhos" (global)
   - Lista de decks individuais
   - Stats mudam baseado na seleção

2. **Overview Cards** (topo, 4 cards - baseado no deck selecionado)
   - Streak atual (dias consecutivos)
   - Total revisões (últimos 30 dias)
   - Taxa de retenção (média qualidade ≥ 3)
   - Próxima revisão programada

3. **Heatmap Calendar** (estilo GitHub)
   - Últimos 90 dias
   - Cor baseada em número de reviews/dia
   - Tooltip ao hover: "X cards revisados em DD/MM (Deck: Y)"
   - Biblioteca: Chart.js ou canvas customizado

4. **Cards por Deck** (se "Todos" selecionado)
   - Barra horizontal mostrando distribuição de cards por deck
   - Cores personalizadas de cada deck

5. **Gráfico de Retenção** (linha)
   - Eixo X: Últimas 4 semanas
   - Eixo Y: Taxa de acertos %
   - Mostra tendência de aprendizado

6. **Cards por Nível** (pizza chart - do deck selecionado)
   - Novo (vermelho)
   - Aprendendo (amarelo)
   - Jovem (verde claro)
   - Maduro (verde escuro)

7. **Últimas Sessões** (tabela)
   - Data, **Deck**, Cards revisados, Tempo, Taxa acerto
   - Link para detalhes da sessão (futuro)

**Bibliotecas**: Chart.js via CDN (já usado no projeto)

---

### FASE 8: Integrações

#### 8.1 Integração com aula.php

**Arquivo**: `aula.php`

**Localização**: Dentro da seção de notas (após linha 252)

**Adicionar botão**:

```html
<!-- Após textarea de notas -->
<?php if (AIHelper::isConfigured() && !empty($anotacao['conteudo'])): ?>
<button
    onclick="abrirModalGerarFlashcards(<?= $aulaId ?>)"
    class="mt-2 px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
    <i class="fas fa-magic mr-2"></i>
    Gerar Flashcards com IA
</button>
<?php endif; ?>
```

**Modal de seleção de deck**:

```html
<!-- Modal que abre ao clicar no botão -->
<div id="modal-selecionar-deck" class="hidden">
    <h3>Escolha o Baralho de Destino</h3>
    <select id="deck-destino">
        <!-- Carregado via AJAX de api/flashcards.php?action=listar_decks -->
    </select>
    <button onclick="gerarFlashcardsDeNota()">Gerar</button>
</div>
```

**JavaScript** (adicionar no final):

```js
function abrirModalGerarFlashcards(aulaId) {
    // Carrega lista de decks
    // Mostra modal de seleção de deck
    window.currentAulaId = aulaId;
}

function gerarFlashcardsDeNota() {
    const deckId = document.getElementById('deck-destino').value;
    // Mostra modal de loading
    // POST api/flashcards.php?action=gerar_de_nota&aula_id=X&deck_id=Y
    // Mostra modal de preview com flashcards gerados
    // Permite editar/desmarcar antes de salvar
}
```

#### 8.2 Integração com sidebar

**Arquivo**: `includes/layout.php`

**Localização**: Após seção de cursos, antes do footer (linha ~120)

**Adicionar**:

```php
<!-- Spaced Repetition Section -->
<div class="mt-8 pt-4 border-t border-gray-700">
    <h3 class="px-4 text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">
        🧠 Revisão Espaçada
    </h3>
    <ul class="space-y-2">
        <li>
            <a href="/flashcards.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-700 rounded-lg">
                <i class="fas fa-layer-group w-5 mr-3"></i>
                <span>Meus Baralhos</span>
            </a>
        </li>
        <li>
            <a href="/revisar_flashcards.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-700 rounded-lg">
                <i class="fas fa-brain w-5 mr-3"></i>
                <span>Revisar Agora</span>
                <span id="cards-due-badge" class="ml-auto hidden bg-red-500 text-white text-xs rounded-full px-2 py-1">0</span>
            </a>
        </li>
        <li>
            <a href="/estatisticas_revisao.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                <span>Estatísticas</span>
            </a>
        </li>
    </ul>
</div>

<script>
// Carregar número de cards devidos
fetch('/api/flashcards.php?action=cards_devidos')
    .then(r => r.json())
    .then(data => {
        if (data.count > 0) {
            document.getElementById('cards-due-badge').textContent = data.count;
            document.getElementById('cards-due-badge').classList.remove('hidden');
        }
    });
</script>
```

---

## Arquivos Críticos

### Criar (5 arquivos novos):

1. `api/flashcards.php` - API de flashcards E decks
2. `api/revisao.php` - API de revisão e SM-2
3. `flashcards.php` - Gerenciamento de baralhos e flashcards
4. `revisar_flashcards.php` - Interface de revisão
5. `estatisticas_revisao.php` - Dashboard de analytics

### Modificar (4 arquivos existentes):

1. `includes/auto_install.php` - Adicionar 6 tabelas (incluindo flashcard_decks)
2. `includes/ai_helper.php` - Adicionar método generateFlashcards()
3. `includes/layout.php` - Adicionar seção no sidebar
4. `aula.php` - Adicionar botão "Gerar Flashcards" com seleção de deck

---

## Compatibilidade Database

**Checklist PostgreSQL vs SQLite**:

- ✅ Auto-increment: `$db->isPostgreSQL() ? 'SERIAL' : 'INTEGER PRIMARY KEY AUTOINCREMENT'`
- ✅ Boolean: `$db->isSQLite() ? '1' : 'TRUE'`
- ✅ Date arithmetic: usar PHP `date()` e `strtotime()` em vez de SQL
- ✅ INSERT pattern: check-then-insert (não usar ON DUPLICATE KEY)
- ✅ JSON: armazenar como TEXT, parse em PHP

---

## Security Checklist

- ✅ Todas queries usam prepared statements (PDO)
- ✅ Ownership: WHERE usuario_id = $_SESSION['user_id']
- ✅ Auth: requireLogin() em todas as páginas
- ✅ XSS: htmlspecialchars() ao renderizar conteúdo
- ✅ CSRF: verificar origin em POSTs sensíveis
- ✅ Rate limiting IA: max 10 flashcards por nota

---

## Testing Strategy

1. **Database**: Testar criação em SQLite e PostgreSQL
2. **SM-2**: Testar edge cases (qualidade 0, 3, 5)
3. **API**: Testar CRUD completo de flashcards
4. **IA**: Testar com nota vazia, nota grande (>5000 chars)
5. **UI**: Testar responsividade mobile
6. **Cross-browser**: Chrome, Firefox, Safari

---

## Features Futuras (Pós-MVP)

- ⏳ Flashcards de erros de simulado
- ⏳ Sugestões de IA sobre timing ótimo
- ⏳ Modo cram (revisar tudo antes de prova)
- ⏳ Compartilhamento de decks entre alunos
- ⏳ Export para Anki (.apkg)
- ⏳ Áudio TTS para flashcards
- ⏳ Gamificação (achievements, leaderboard)

---

## Ordem de Implementação Recomendada

1. **Database** (auto_install.php - 6 tabelas com decks) - 45min
2. **API Decks** (CRUD de baralhos) - 1.5h
3. **API Flashcards** (CRUD vinculado a decks) - 2h
4. **API Revisão** (SM-2 + sessões por deck) - 2h
5. **AI Helper** (generateFlashcards) - 1h
6. **UI Flashcards** (gerenciamento com sidebar de decks) - 4h
7. **UI Revisão** (interface interativa por deck) - 3h
8. **UI Estatísticas** (heatmap + charts + filtro por deck) - 2.5h
9. **Integrações** (sidebar + aula.php com seleção de deck) - 1.5h
10. **Testes** - 2h
11. **Polish e ajustes** - 2h

**Total estimado**: 22-24 horas de desenvolvimento

---

## Critérios de Sucesso

✅ Aluno pode criar múltiplos baralhos personalizados (Inglês, AZ-900, Python, etc.)
✅ Aluno pode criar flashcards manualmente em qualquer baralho
✅ Aluno pode gerar flashcards de notas com IA escolhendo o baralho destino
✅ Sistema agenda revisões usando SM-2 (por baralho ou global)
✅ Interface de revisão funcional (flip, rating 0-5) filtrável por baralho
✅ Estatísticas mostram progresso por baralho ou global (heatmap)
✅ Badge no sidebar mostra cards devidos (todos os baralhos)
✅ Funciona em PostgreSQL e SQLite
✅ Responsivo (mobile + desktop)
✅ Baralhos têm cores e ícones personalizáveis

---

**Pronto para implementar! 🚀**
