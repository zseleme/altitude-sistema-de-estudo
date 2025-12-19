<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$simulado_id = $_GET['id'] ?? 0;

// Buscar informações do simulado
$database = Database::getInstance();
$db = $database->getConnection();

$query = "SELECT * FROM simulados WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $simulado_id, PDO::PARAM_INT);
$stmt->execute();
$simulado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$simulado) {
    header('Location: admin_simulados.php');
    exit;
}

$content = '
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <a href="admin_simulados.php" class="text-blue-600 hover:text-blue-800 mr-4">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">' . htmlspecialchars($simulado['titulo']) . '</h1>
                <p class="text-gray-600">' . htmlspecialchars($simulado['descricao'] ?? 'Sem descrição') . '</p>
                <div class="mt-2 text-sm text-gray-500">
                    <span class="inline-flex items-center mr-4">
                        <i class="fas fa-book mr-1"></i>
                        Disciplina: ' . htmlspecialchars($simulado['disciplina'] ?? 'Não definida') . '
                    </span>
                    <span class="inline-flex items-center">
                        <i class="fas fa-clock mr-1"></i>
                        Tempo: ' . ($simulado['tempo_limite'] ? $simulado['tempo_limite'] . ' min' : 'Sem limite') . '
                    </span>
                </div>
            </div>
            <button onclick="abrirModalCadastroMassivo()" class="bg-blue-600 text-white px-6 py-3 text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Adicionar Questões
            </button>
        </div>
    </div>

    <!-- Lista de Questões -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Questões</h2>
        </div>
        <div class="p-6">
            <div id="listaQuestoes" class="space-y-4">
                <!-- Carregado via JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Cadastro Massivo -->
<div id="modalCadastroMassivo" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl my-8">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Cadastro Massivo de Questões</h3>
                <button onclick="fecharModalCadastroMassivo()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-blue-900 text-sm"><strong>Como usar:</strong> Cole as questões no formato JSON abaixo</p>
                            <p class="text-blue-700 text-xs mt-1">Questões podem ter de 2 a 5 alternativas. Campos obrigatórios: enunciado, alternativa_a, alternativa_b e resposta_correta</p>
                        </div>
                        <button onclick="mostrarExemploJSON()" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">Ver Exemplo</button>
                    </div>
                </div>

                <div id="exemploJSON" class="hidden bg-gray-50 p-4 rounded-lg mb-4 overflow-x-auto">
                    <pre class="text-xs"><code>[
  {
    "enunciado": "Qual a capital do Brasil?",
    "alternativa_a": "São Paulo",
    "alternativa_b": "Rio de Janeiro",
    "alternativa_c": "Brasília",
    "alternativa_d": "Salvador",
    "alternativa_e": "",
    "resposta_correta": "C",
    "explicacao": "Brasília é a capital desde 1960.",
    "nivel_dificuldade": "facil",
    "tags": "geografia, brasil",
    "texto_apoio": ""
  },
  {
    "enunciado": "2 + 2 é igual a:",
    "alternativa_a": "3",
    "alternativa_b": "4",
    "resposta_correta": "B",
    "explicacao": "Questão com apenas 2 alternativas.",
    "nivel_dificuldade": "facil",
    "tags": "matematica"
  }
]</code></pre>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">JSON das Questões</label>
                    <textarea id="questoesJSON" rows="12" placeholder=\'[{"enunciado": "...", ...}]\' class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>

                <div class="flex items-center space-x-3 mb-4">
                    <button onclick="validarJSON()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors inline-flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Validar
                    </button>
                    <span id="validacaoStatus"></span>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="fecharModalCadastroMassivo()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">Cancelar</button>
                <button onclick="salvarQuestoesMassivo()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Questão -->
<div id="modalEditarQuestao" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl my-8">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Editar Questão</h3>
                <button onclick="fecharModalEditarQuestao()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="formEditarQuestao" class="p-6 space-y-4">
                <input type="hidden" id="edit_questao_id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enunciado *</label>
                    <textarea id="edit_enunciado" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alternativa A *</label>
                        <input type="text" id="edit_alternativa_a" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alternativa B *</label>
                        <input type="text" id="edit_alternativa_b" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alternativa C</label>
                        <input type="text" id="edit_alternativa_c" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alternativa D</label>
                        <input type="text" id="edit_alternativa_d" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alternativa E</label>
                        <input type="text" id="edit_alternativa_e" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resposta Correta *</label>
                        <select id="edit_resposta_correta" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Explicação</label>
                    <textarea id="edit_explicacao" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Texto de Apoio</label>
                    <textarea id="edit_texto_apoio" rows="3" placeholder="Texto adicional para ajudar o aluno a responder a questão" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nível de Dificuldade</label>
                        <select id="edit_nivel_dificuldade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="facil">Fácil</option>
                            <option value="medio">Médio</option>
                            <option value="dificil">Difícil</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <input type="text" id="edit_tags" placeholder="Separadas por vírgula" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </form>
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="fecharModalEditarQuestao()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">Cancelar</button>
                <button onclick="salvarEdicaoQuestao()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
const simuladoId = ' . $simulado_id . ';

document.addEventListener(\'DOMContentLoaded\', carregarQuestoes);

async function carregarQuestoes() {
    try {
        const response = await fetch(`api/questoes.php?action=listar&simulado_id=${simuladoId}`);
        const questoes = await response.json();

        const container = document.getElementById(\'listaQuestoes\');

        if (questoes.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>Nenhuma questão cadastrada ainda</p>
                </div>
            `;
            return;
        }

        container.innerHTML = questoes.map(q => `
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                Questão ${q.numero_questao}
                            </span>
                            ${q.nivel_dificuldade ? `
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                    q.nivel_dificuldade === \'facil\' ? \'bg-green-100 text-green-800\' :
                                    q.nivel_dificuldade === \'medio\' ? \'bg-yellow-100 text-yellow-800\' :
                                    \'bg-red-100 text-red-800\'
                                }">
                                    ${q.nivel_dificuldade.charAt(0).toUpperCase() + q.nivel_dificuldade.slice(1)}
                                </span>
                            ` : \'\'}
                        </div>
                        <p class="text-gray-900 font-medium mb-3">${q.enunciado}</p>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-700"><strong>A)</strong> ${q.alternativa_a}</p>
                            <p class="text-gray-700"><strong>B)</strong> ${q.alternativa_b}</p>
                            ${q.alternativa_c ? `<p class="text-gray-700"><strong>C)</strong> ${q.alternativa_c}</p>` : \'\'}
                            ${q.alternativa_d ? `<p class="text-gray-700"><strong>D)</strong> ${q.alternativa_d}</p>` : \'\'}
                            ${q.alternativa_e ? `<p class="text-gray-700"><strong>E)</strong> ${q.alternativa_e}</p>` : \'\'}
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-sm text-green-700"><strong>Resposta:</strong> ${q.resposta_correta}</p>
                            ${q.explicacao ? `<p class="text-sm text-gray-600 mt-1"><strong>Explicação:</strong> ${q.explicacao}</p>` : \'\'}
                        </div>
                    </div>
                    <div class="flex space-x-2 ml-4">
                        <button onclick="editarQuestao(${q.id})" class="text-blue-600 hover:text-blue-800" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deletarQuestao(${q.id})" class="text-red-600 hover:text-red-800" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join(\'\');
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao carregar questões\');
    }
}

function abrirModalCadastroMassivo() {
    document.getElementById(\'questoesJSON\').value = \'\';
    document.getElementById(\'validacaoStatus\').innerHTML = \'\';
    document.getElementById(\'modalCadastroMassivo\').classList.remove(\'hidden\');
}

function fecharModalCadastroMassivo() {
    document.getElementById(\'modalCadastroMassivo\').classList.add(\'hidden\');
}

function mostrarExemploJSON() {
    document.getElementById(\'exemploJSON\').classList.toggle(\'hidden\');
}

function validarJSON() {
    const textarea = document.getElementById(\'questoesJSON\');
    const status = document.getElementById(\'validacaoStatus\');

    try {
        const questoes = JSON.parse(textarea.value);

        if (!Array.isArray(questoes)) {
            throw new Error(\'O JSON deve ser um array\');
        }

        questoes.forEach((q, index) => {
            // Função auxiliar para verificar se um campo está preenchido
            const isPreenchido = (valor) => {
                return valor !== undefined && valor !== null && String(valor).trim() !== \'\';
            };

            // Campos obrigatórios: enunciado, alternativa_a, alternativa_b e resposta_correta
            if (!isPreenchido(q.enunciado)) {
                throw new Error(`Questão ${index + 1}: campo "enunciado" obrigatório`);
            }
            if (!isPreenchido(q.alternativa_a)) {
                throw new Error(`Questão ${index + 1}: campo "alternativa_a" obrigatório`);
            }
            if (!isPreenchido(q.alternativa_b)) {
                throw new Error(`Questão ${index + 1}: campo "alternativa_b" obrigatório`);
            }
            if (!isPreenchido(q.resposta_correta)) {
                throw new Error(`Questão ${index + 1}: campo "resposta_correta" obrigatório`);
            }

            // Validar resposta_correta
            const respostaUpper = String(q.resposta_correta).toUpperCase().trim();
            if (![\'A\', \'B\', \'C\', \'D\', \'E\'].includes(respostaUpper)) {
                throw new Error(`Questão ${index + 1}: resposta_correta deve ser A, B, C, D ou E`);
            }

            // Verificar se a alternativa marcada como correta existe e não está vazia
            const alternativaMap = {
                \'A\': q.alternativa_a,
                \'B\': q.alternativa_b,
                \'C\': q.alternativa_c,
                \'D\': q.alternativa_d,
                \'E\': q.alternativa_e
            };

            if (!isPreenchido(alternativaMap[respostaUpper])) {
                throw new Error(`Questão ${index + 1}: a resposta correta é "${respostaUpper}", mas a alternativa ${respostaUpper} está vazia ou não foi fornecida`);
            }
        });

        status.innerHTML = `<span class="text-green-600 text-sm flex items-center"><i class="fas fa-check-circle mr-1"></i>JSON válido! ${questoes.length} questões prontas.</span>`;
        return true;
    } catch (error) {
        status.innerHTML = `<span class="text-red-600 text-sm flex items-center"><i class="fas fa-times-circle mr-1"></i>${error.message}</span>`;
        return false;
    }
}

async function salvarQuestoesMassivo() {
    if (!validarJSON()) return;

    const questoes = JSON.parse(document.getElementById(\'questoesJSON\').value);

    try {
        const response = await fetch(\'api/questoes.php?action=cadastrar_massivo\', {
            method: \'POST\',
            headers: {\'Content-Type\': \'application/json\'},
            body: JSON.stringify({
                simulado_id: simuladoId,
                questoes: questoes
            })
        });

        const result = await response.json();

        if (result.success) {
            fecharModalCadastroMassivo();
            carregarQuestoes();
            alert(`${result.total} questões cadastradas com sucesso!`);
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao cadastrar questões\');
    }
}

async function editarQuestao(id) {
    try {
        const response = await fetch(`api/questoes.php?action=listar&simulado_id=${simuladoId}`);
        const questoes = await response.json();
        const questao = questoes.find(q => q.id == id);

        if (!questao) {
            alert(\'Questão não encontrada\');
            return;
        }

        document.getElementById(\'edit_questao_id\').value = questao.id;
        document.getElementById(\'edit_enunciado\').value = questao.enunciado;
        document.getElementById(\'edit_alternativa_a\').value = questao.alternativa_a;
        document.getElementById(\'edit_alternativa_b\').value = questao.alternativa_b;
        document.getElementById(\'edit_alternativa_c\').value = questao.alternativa_c || \'\';
        document.getElementById(\'edit_alternativa_d\').value = questao.alternativa_d || \'\';
        document.getElementById(\'edit_alternativa_e\').value = questao.alternativa_e || \'\';
        document.getElementById(\'edit_resposta_correta\').value = questao.resposta_correta;
        document.getElementById(\'edit_explicacao\').value = questao.explicacao || \'\';
        document.getElementById(\'edit_texto_apoio\').value = questao.texto_apoio || \'\';
        document.getElementById(\'edit_nivel_dificuldade\').value = questao.nivel_dificuldade || \'facil\';
        document.getElementById(\'edit_tags\').value = questao.tags || \'\';

        document.getElementById(\'modalEditarQuestao\').classList.remove(\'hidden\');
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao carregar questão\');
    }
}

function fecharModalEditarQuestao() {
    document.getElementById(\'modalEditarQuestao\').classList.add(\'hidden\');
}

async function salvarEdicaoQuestao() {
    const data = {
        id: document.getElementById(\'edit_questao_id\').value,
        enunciado: document.getElementById(\'edit_enunciado\').value,
        alternativa_a: document.getElementById(\'edit_alternativa_a\').value,
        alternativa_b: document.getElementById(\'edit_alternativa_b\').value,
        alternativa_c: document.getElementById(\'edit_alternativa_c\').value,
        alternativa_d: document.getElementById(\'edit_alternativa_d\').value,
        alternativa_e: document.getElementById(\'edit_alternativa_e\').value,
        resposta_correta: document.getElementById(\'edit_resposta_correta\').value,
        explicacao: document.getElementById(\'edit_explicacao\').value,
        texto_apoio: document.getElementById(\'edit_texto_apoio\').value,
        nivel_dificuldade: document.getElementById(\'edit_nivel_dificuldade\').value,
        tags: document.getElementById(\'edit_tags\').value
    };

    try {
        const response = await fetch(\'api/questoes.php?action=editar\', {
            method: \'POST\',
            headers: {\'Content-Type\': \'application/json\'},
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            fecharModalEditarQuestao();
            carregarQuestoes();
            alert(\'Questão editada com sucesso!\');
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao editar questão\');
    }
}

async function deletarQuestao(id) {
    if (!confirm(\'Tem certeza que deseja excluir esta questão?\')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append(\'id\', id);

        const response = await fetch(\'api/questoes.php?action=deletar\', {
            method: \'POST\',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            carregarQuestoes();
            alert(\'Questão excluída com sucesso!\');
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao excluir questão\');
    }
}
</script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Visualizar Questões - ' . $simulado['titulo'], $content, true, true);
?>
