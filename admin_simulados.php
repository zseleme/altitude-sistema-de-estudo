<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$content = '
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Gerenciar Simulados</h1>
            <p class="text-gray-600">Crie e gerencie simulados para os estudantes</p>
        </div>
        <button onclick="abrirModalNovoSimulado()" class="bg-blue-600 text-white px-6 py-3 text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Novo Simulado
        </button>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" id="tabelaSimulados">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disciplina</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Questões</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tempo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Carregado via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Novo Simulado -->
<div id="modalNovoSimulado" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Novo Simulado</h3>
                <button onclick="fecharModalNovoSimulado()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="formNovoSimulado" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                        <input type="text" name="titulo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea name="descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                        <input type="text" name="disciplina" placeholder="Ex: Matemática, História" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempo Limite (minutos)</label>
                        <input type="number" name="tempo_limite" placeholder="0 = sem limite" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </form>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="fecharModalNovoSimulado()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">Cancelar</button>
                <button onclick="criarSimulado()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">Criar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Simulado -->
<div id="modalEditarSimulado" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Editar Simulado</h3>
                <button onclick="fecharModalEditarSimulado()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="formEditarSimulado" class="space-y-4">
                    <input type="hidden" id="edit_simulado_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                        <input type="text" id="edit_titulo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea id="edit_descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                        <input type="text" id="edit_disciplina" placeholder="Ex: Matemática, História" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempo Limite (minutos)</label>
                        <input type="number" id="edit_tempo_limite" placeholder="0 = sem limite" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </form>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="fecharModalEditarSimulado()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">Cancelar</button>
                <button onclick="salvarEdicaoSimulado()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">Salvar</button>
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
                <input type="hidden" id="simuladoIdMassivo">

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                    <div class="flex justify-between items-center">
                        <p class="text-blue-900 text-sm"><strong>Como usar:</strong> Cole as questões no formato JSON abaixo</p>
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
    "tags": "geografia, brasil"
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

<script>
let simulados = [];

document.addEventListener(\'DOMContentLoaded\', carregarSimulados);

async function carregarSimulados() {
    try {
        const response = await fetch(\'api/simulados.php?action=listar\');
        simulados = await response.json();

        const tbody = document.querySelector(\'#tabelaSimulados tbody\');
        tbody.innerHTML = \'\';

        simulados.forEach(sim => {
            const tr = document.createElement(\'tr\');
            tr.className = \'hover:bg-gray-50\';
            tr.innerHTML = `
                <td class="px-6 py-4 text-sm text-gray-900">${sim.id}</td>
                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${sim.titulo}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${sim.disciplina || \'-\'}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${sim.total_questoes || 0}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">${sim.tempo_limite ? sim.tempo_limite + \' min\' : \'Sem limite\'}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${sim.ativo == 1 ? \'bg-green-100 text-green-800\' : \'bg-gray-100 text-gray-800\'}">
                        ${sim.ativo == 1 ? \'Ativo\' : \'Inativo\'}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <button onclick="visualizarQuestoes(${sim.id})" class="text-gray-600 hover:text-gray-800" title="Ver Questões">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editarSimulado(${sim.id})" class="text-blue-600 hover:text-blue-800" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="excluirSimulado(${sim.id})" class="text-red-600 hover:text-red-800" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao carregar simulados\');
    }
}

function abrirModalNovoSimulado() {
    document.getElementById(\'modalNovoSimulado\').classList.remove(\'hidden\');
}

function fecharModalNovoSimulado() {
    document.getElementById(\'modalNovoSimulado\').classList.add(\'hidden\');
    document.getElementById(\'formNovoSimulado\').reset();
}

async function criarSimulado() {
    const form = document.getElementById(\'formNovoSimulado\');
    const formData = new FormData(form);

    const data = {
        titulo: formData.get(\'titulo\'),
        descricao: formData.get(\'descricao\'),
        disciplina: formData.get(\'disciplina\'),
        tempo_limite: formData.get(\'tempo_limite\') || 0
    };

    try {
        const response = await fetch(\'api/simulados.php?action=criar\', {
            method: \'POST\',
            headers: {\'Content-Type\': \'application/json\'},
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            fecharModalNovoSimulado();
            carregarSimulados();
            alert(\'Simulado criado com sucesso!\');
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao criar simulado\');
    }
}

function abrirCadastroMassivo(simuladoId) {
    document.getElementById(\'simuladoIdMassivo\').value = simuladoId;
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
            const campos = [\'enunciado\', \'alternativa_a\', \'alternativa_b\', \'alternativa_c\', \'alternativa_d\', \'resposta_correta\'];
            campos.forEach(campo => {
                if (!q[campo]) throw new Error(`Questão ${index + 1}: campo "${campo}" obrigatório`);
            });

            if (![\'A\', \'B\', \'C\', \'D\', \'E\'].includes(q.resposta_correta.toUpperCase())) {
                throw new Error(`Questão ${index + 1}: resposta_correta deve ser A, B, C, D ou E`);
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

    const simuladoId = document.getElementById(\'simuladoIdMassivo\').value;
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
            carregarSimulados();
            alert(`${result.total} questões cadastradas com sucesso!`);
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao cadastrar questões\');
    }
}

function visualizarQuestoes(simuladoId) {
    window.location.href = `visualizar_questoes.php?id=${simuladoId}`;
}

async function editarSimulado(simuladoId) {
    const simulado = simulados.find(s => s.id == simuladoId);

    if (!simulado) {
        alert(\'Simulado não encontrado\');
        return;
    }

    document.getElementById(\'edit_simulado_id\').value = simulado.id;
    document.getElementById(\'edit_titulo\').value = simulado.titulo;
    document.getElementById(\'edit_descricao\').value = simulado.descricao || \'\';
    document.getElementById(\'edit_disciplina\').value = simulado.disciplina || \'\';
    document.getElementById(\'edit_tempo_limite\').value = simulado.tempo_limite || 0;

    document.getElementById(\'modalEditarSimulado\').classList.remove(\'hidden\');
}

function fecharModalEditarSimulado() {
    document.getElementById(\'modalEditarSimulado\').classList.add(\'hidden\');
}

async function salvarEdicaoSimulado() {
    const data = {
        id: document.getElementById(\'edit_simulado_id\').value,
        titulo: document.getElementById(\'edit_titulo\').value,
        descricao: document.getElementById(\'edit_descricao\').value,
        disciplina: document.getElementById(\'edit_disciplina\').value,
        tempo_limite: document.getElementById(\'edit_tempo_limite\').value || 0
    };

    try {
        const response = await fetch(\'api/simulados.php?action=editar\', {
            method: \'POST\',
            headers: {\'Content-Type\': \'application/json\'},
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            fecharModalEditarSimulado();
            carregarSimulados();
            alert(\'Simulado atualizado com sucesso!\');
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao atualizar simulado\');
    }
}

async function excluirSimulado(simuladoId) {
    if (!confirm(\'Tem certeza que deseja excluir este simulado? Esta ação não pode ser desfeita.\')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append(\'id\', simuladoId);

        const response = await fetch(\'api/simulados.php?action=excluir\', {
            method: \'POST\',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            carregarSimulados();
            alert(\'Simulado excluído com sucesso!\');
        } else {
            alert(\'Erro: \' + result.error);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao excluir simulado\');
    }
}
</script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Gerenciar Simulados', $content, true, true);
?>
