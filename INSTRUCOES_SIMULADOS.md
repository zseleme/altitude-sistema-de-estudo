# Sistema de Simulados - Instruções de Instalação e Uso

## Instalação

### 1. Executar Migração do Banco de Dados

Execute o seguinte comando no terminal (ou acesse o arquivo diretamente pelo navegador):

```bash
php migrations/add_simulados_table.php
```

Ou acesse via navegador:
```
http://localhost/migrations/add_simulados_table.php
```

Isso criará as seguintes tabelas:
- `simulados` - Armazena os simulados
- `simulado_questoes` - Armazena as questões de cada simulado
- `simulado_respostas` - Armazena as respostas dos alunos
- `simulado_tentativas` - Rastreia as tentativas de cada aluno

## Funcionalidades Implementadas

### Para Administradores

#### 1. Gerenciar Simulados (`admin_simulados.php`)
- Criar novos simulados
- Definir título, descrição, disciplina e tempo limite
- Visualizar lista de todos os simulados

#### 2. Cadastro Massivo de Questões
- Importar múltiplas questões de uma vez via JSON
- Formato das questões:

```json
[
  {
    "enunciado": "Qual a capital do Brasil?",
    "alternativa_a": "São Paulo",
    "alternativa_b": "Rio de Janeiro",
    "alternativa_c": "Brasília",
    "alternativa_d": "Salvador",
    "alternativa_e": "",
    "resposta_correta": "C",
    "explicacao": "Brasília é a capital federal do Brasil desde 1960.",
    "nivel_dificuldade": "facil",
    "tags": "geografia, brasil, capitais"
  }
]
```

**Campos obrigatórios:**
- `enunciado`
- `alternativa_a`, `alternativa_b`, `alternativa_c`, `alternativa_d`
- `resposta_correta` (A, B, C, D ou E)

**Campos opcionais:**
- `alternativa_e`
- `explicacao` (recomendado)
- `nivel_dificuldade` (facil, medio, dificil)
- `tags` (separadas por vírgula)

### Para Alunos

#### 1. Listagem de Simulados (`simulados.php`)
- Visualizar todos os simulados disponíveis
- Ver estatísticas pessoais (simulados realizados, média geral)
- Iniciar ou refazer simulados

#### 2. Realizar Simulado (`realizar_simulado.php`)
- Interface moderna e intuitiva
- Timer com contagem regressiva (se configurado)
- Navegação entre questões
- Barra de progresso
- Seleção de alternativas com feedback visual
- Correção imediata ao responder
- **Análise por IA para respostas incorretas**

#### 3. Análise por IA
Quando o aluno erra uma questão, a IA (Google Gemini) gera uma análise pedagógica incluindo:

- **Por que você errou**: Explicação do erro de raciocínio
- **A lógica correta**: Conceito explicado de forma clara
- **Para nunca mais esquecer**: Técnica mnemônica, analogia ou macete
- **Dica rápida**: Frase resumida

A IA atua como um professor de cursinho experiente, usando métodos para ajudar na memorização.

#### 4. Resultado do Simulado (`resultado_simulado.php`)
- Nota final e estatísticas detalhadas
- Revisão completa de todas as questões
- Filtro por questões corretas/incorretas
- Visualização da análise da IA para cada erro
- Opção de impressão

## Estrutura de Arquivos

```
/migrations/
  └── add_simulados_table.php          # Migração do banco de dados

/api/
  ├── simulados.php                     # API de gerenciamento de simulados
  ├── questoes.php                      # API de cadastro de questões
  └── analise_questao_ia.php           # API de análise por IA

/pages/
  ├── admin_simulados.php              # Admin - Gerenciar simulados
  ├── simulados.php                     # Aluno - Listar simulados
  ├── realizar_simulado.php             # Aluno - Fazer simulado
  └── resultado_simulado.php            # Aluno - Ver resultado
```

## Endpoints da API

### Simulados (`api/simulados.php`)

- `GET ?action=listar` - Lista todos os simulados ativos
- `POST ?action=criar` - Cria um novo simulado (admin)
- `GET ?action=detalhes&id=X` - Detalhes de um simulado específico
- `POST ?action=iniciar` - Inicia uma tentativa de simulado
- `POST ?action=responder` - Registra resposta de uma questão
- `POST ?action=finalizar` - Finaliza simulado e calcula nota
- `GET ?action=resultado&tentativa_id=X` - Resultado detalhado

### Questões (`api/questoes.php`)

- `POST ?action=cadastrar_massivo` - Cadastro em lote (JSON)
- `GET ?action=listar&simulado_id=X` - Lista questões de um simulado
- `POST ?action=editar` - Edita uma questão
- `POST ?action=deletar` - Deleta uma questão

### Análise IA (`api/analise_questao_ia.php`)

- `POST` com `resposta_id` - Gera análise pedagógica da IA

## Fluxo de Uso

### Para Administradores:

1. Acesse "Simulados" no menu de Administração
2. Clique em "Novo Simulado"
3. Preencha os dados e salve
4. Clique no botão "+" para adicionar questões
5. Cole o JSON com as questões no formato especificado
6. Valide e salve

### Para Alunos:

1. Acesse "Simulados" no menu principal
2. Escolha um simulado e clique em "Iniciar Simulado"
3. Responda as questões uma por uma
4. Para cada resposta incorreta, veja a análise da IA
5. Navegue entre as questões usando os botões ou o painel lateral
6. Finalize o simulado para ver o resultado completo

## Recursos Especiais

### Timer Inteligente
- Se o simulado tiver tempo limite configurado, um timer aparecerá
- Ao finalizar o tempo, o simulado é automaticamente enviado

### Análise por IA
- Usa o Google Gemini para gerar análises pedagógicas
- Cache de análises (não gera novamente para a mesma resposta)
- Foco em técnicas mnemônicas e memorização

### Navegação Intuitiva
- Questões numeradas no painel lateral
- Indicador visual de questões respondidas
- Progress bar mostrando andamento

### Feedback Visual
- Alternativas corretas em verde
- Alternativas incorretas em vermelho
- Destaque para a resposta selecionada

## Próximos Passos Sugeridos

- [ ] Adicionar filtros por disciplina na listagem de simulados
- [ ] Implementar gráficos de desempenho ao longo do tempo
- [ ] Adicionar ranking entre alunos
- [ ] Exportar resultados em PDF
- [ ] Questões com imagens
- [ ] Questões dissertativas com correção por IA
- [ ] Banco de questões compartilhado
- [ ] Simulados adaptativos (dificuldade ajustada pelo desempenho)

## Suporte

Para dúvidas ou problemas, verifique:
1. Se a migração foi executada corretamente
2. Se a API do Gemini está configurada em `config/gemini.php`
3. Se as permissões de arquivos estão corretas
4. Logs de erro do PHP/servidor

---

**Desenvolvido com foco na experiência do usuário e aprendizado efetivo!**
