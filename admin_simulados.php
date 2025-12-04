<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = 'Gerenciar Simulados';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gerenciar Simulados</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoSimuladoModal">
            <i class="bi bi-plus-circle"></i> Novo Simulado
        </button>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabelaSimulados">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Disciplina</th>
                                    <th>Questões</th>
                                    <th>Tempo Limite</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Carregado via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Simulado -->
<div class="modal fade" id="novoSimuladoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Simulado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoSimulado">
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Disciplina</label>
                        <input type="text" class="form-control" name="disciplina" placeholder="Ex: Matemática, História, etc">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tempo Limite (minutos)</label>
                        <input type="number" class="form-control" name="tempo_limite" placeholder="0 = sem limite">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="criarSimulado()">Criar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cadastro Massivo -->
<div class="modal fade" id="cadastroMassivoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastro Massivo de Questões</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="simuladoIdMassivo">

                <div class="alert alert-info">
                    <strong>Como usar:</strong> Cole ou digite as questões no formato JSON abaixo. Você pode adicionar múltiplas questões de uma vez.
                    <button class="btn btn-sm btn-outline-primary float-end" onclick="mostrarExemploJSON()">Ver Exemplo</button>
                </div>

                <div id="exemploJSON" style="display: none;" class="mb-3">
                    <pre class="bg-light p-3 rounded"><code>[
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
  },
  {
    "enunciado": "Quanto é 2 + 2?",
    "alternativa_a": "3",
    "alternativa_b": "4",
    "alternativa_c": "5",
    "alternativa_d": "6",
    "alternativa_e": "7",
    "resposta_correta": "B",
    "explicacao": "2 + 2 = 4. Soma básica de aritmética.",
    "nivel_dificuldade": "facil",
    "tags": "matematica, aritmetica"
  }
]</code></pre>
                </div>

                <div class="mb-3">
                    <label class="form-label">JSON das Questões</label>
                    <textarea class="form-control font-monospace" id="questoesJSON" rows="15" placeholder='[{"enunciado": "...", "alternativa_a": "...", ...}]'></textarea>
                </div>

                <div class="mb-3">
                    <button class="btn btn-outline-secondary" onclick="validarJSON()">
                        <i class="bi bi-check-circle"></i> Validar JSON
                    </button>
                    <span id="validacaoStatus" class="ms-2"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarQuestoesMassivo()">
                    <i class="bi bi-save"></i> Salvar Questões
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let simulados = [];

document.addEventListener('DOMContentLoaded', function() {
    carregarSimulados();
});

async function carregarSimulados() {
    try {
        const response = await fetch('api/simulados.php?action=listar');
        simulados = await response.json();

        const tbody = document.querySelector('#tabelaSimulados tbody');
        tbody.innerHTML = '';

        simulados.forEach(sim => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${sim.id}</td>
                <td>${sim.titulo}</td>
                <td>${sim.disciplina || '-'}</td>
                <td><span class="badge bg-primary">${sim.total_questoes || 0}</span></td>
                <td>${sim.tempo_limite ? sim.tempo_limite + ' min' : 'Sem limite'}</td>
                <td>
                    <span class="badge bg-${sim.ativo == 1 ? 'success' : 'secondary'}">
                        ${sim.ativo == 1 ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="abrirCadastroMassivo(${sim.id})" title="Adicionar Questões">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="visualizarQuestoes(${sim.id})" title="Ver Questões">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar simulados:', error);
        alert('Erro ao carregar simulados');
    }
}

async function criarSimulado() {
    const form = document.getElementById('formNovoSimulado');
    const formData = new FormData(form);

    const data = {
        titulo: formData.get('titulo'),
        descricao: formData.get('descricao'),
        disciplina: formData.get('disciplina'),
        tempo_limite: formData.get('tempo_limite') || 0
    };

    try {
        const response = await fetch('api/simulados.php?action=criar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('novoSimuladoModal')).hide();
            form.reset();
            carregarSimulados();
            alert('Simulado criado com sucesso!');
        } else {
            alert('Erro ao criar simulado: ' + result.error);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao criar simulado');
    }
}

function abrirCadastroMassivo(simuladoId) {
    document.getElementById('simuladoIdMassivo').value = simuladoId;
    document.getElementById('questoesJSON').value = '';
    document.getElementById('validacaoStatus').innerHTML = '';
    const modal = new bootstrap.Modal(document.getElementById('cadastroMassivoModal'));
    modal.show();
}

function mostrarExemploJSON() {
    const exemplo = document.getElementById('exemploJSON');
    exemplo.style.display = exemplo.style.display === 'none' ? 'block' : 'none';
}

function validarJSON() {
    const textarea = document.getElementById('questoesJSON');
    const status = document.getElementById('validacaoStatus');

    try {
        const questoes = JSON.parse(textarea.value);

        if (!Array.isArray(questoes)) {
            throw new Error('O JSON deve ser um array de questões');
        }

        questoes.forEach((q, index) => {
            const campos = ['enunciado', 'alternativa_a', 'alternativa_b', 'alternativa_c', 'alternativa_d', 'resposta_correta'];
            campos.forEach(campo => {
                if (!q[campo]) {
                    throw new Error(`Questão ${index + 1}: campo "${campo}" é obrigatório`);
                }
            });

            if (!['A', 'B', 'C', 'D', 'E'].includes(q.resposta_correta.toUpperCase())) {
                throw new Error(`Questão ${index + 1}: resposta_correta deve ser A, B, C, D ou E`);
            }
        });

        status.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i> JSON válido! ${questoes.length} questões prontas para importar.</span>`;
        return true;
    } catch (error) {
        status.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle"></i> Erro: ${error.message}</span>`;
        return false;
    }
}

async function salvarQuestoesMassivo() {
    if (!validarJSON()) {
        return;
    }

    const simuladoId = document.getElementById('simuladoIdMassivo').value;
    const questoes = JSON.parse(document.getElementById('questoesJSON').value);

    try {
        const response = await fetch('api/questoes.php?action=cadastrar_massivo', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                simulado_id: simuladoId,
                questoes: questoes
            })
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('cadastroMassivoModal')).hide();
            carregarSimulados();
            alert(`${result.total} questões cadastradas com sucesso!`);
        } else {
            alert('Erro ao cadastrar questões: ' + result.error);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao cadastrar questões');
    }
}

function visualizarQuestoes(simuladoId) {
    window.location.href = `visualizar_questoes.php?id=${simuladoId}`;
}
</script>

<?php include 'includes/footer.php'; ?>
