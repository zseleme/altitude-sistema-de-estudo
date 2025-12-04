<?php
session_start();
require_once 'includes/auth.php';

requireLogin();

$simulado_id = $_GET['id'] ?? 0;
$tentativa_id = $_GET['tentativa'] ?? 0;

$page_title = 'Realizar Simulado';
include 'includes/header.php';
?>

<style>
.questao-card {
    transition: all 0.3s ease;
}

.alternativa-item {
    cursor: pointer;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: all 0.2s;
}

.alternativa-item:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}

.alternativa-item input[type="radio"] {
    cursor: pointer;
    margin-right: 10px;
}

.alternativa-item.selecionada {
    background-color: #e7f3ff;
    border-color: #0d6efd;
    font-weight: 500;
}

.alternativa-item.correta {
    background-color: #d4edda;
    border-color: #28a745;
}

.alternativa-item.incorreta {
    background-color: #f8d7da;
    border-color: #dc3545;
}

.timer-box {
    position: sticky;
    top: 70px;
    z-index: 100;
}

.analise-ia {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.navegacao-questoes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.btn-questao {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    font-weight: 600;
}

.btn-questao.respondida {
    background-color: #0d6efd;
    color: white;
}

.btn-questao.atual {
    border: 3px solid #0d6efd;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Área principal do simulado -->
        <div class="col-lg-9">
            <div class="card questao-card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0" id="tituloSimulado">Carregando...</h5>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark">
                                Questão <span id="questaoAtual">1</span> de <span id="totalQuestoes">0</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body" id="areaQuestao">
                    <!-- Carregado via JavaScript -->
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-secondary" id="btnAnterior" onclick="questaoAnterior()" disabled>
                            <i class="bi bi-arrow-left"></i> Anterior
                        </button>
                        <button class="btn btn-primary" id="btnResponder" onclick="responderQuestao()" disabled>
                            Responder
                        </button>
                        <button class="btn btn-primary" id="btnProxima" onclick="proximaQuestao()" style="display: none;">
                            Próxima <i class="bi bi-arrow-right"></i>
                        </button>
                        <button class="btn btn-success" id="btnFinalizar" onclick="finalizarSimulado()" style="display: none;">
                            <i class="bi bi-check-circle"></i> Finalizar Simulado
                        </button>
                    </div>
                </div>
            </div>

            <!-- Área de análise da IA -->
            <div id="areaAnaliseIA" style="display: none;"></div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <!-- Timer -->
            <div class="card timer-box mb-3" id="timerCard" style="display: none;">
                <div class="card-body text-center">
                    <h6 class="text-muted">Tempo Restante</h6>
                    <h2 class="mb-0" id="timer">00:00</h2>
                </div>
            </div>

            <!-- Navegação de questões -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Navegação</h6>
                </div>
                <div class="card-body">
                    <div class="navegacao-questoes" id="navegacaoQuestoes">
                        <!-- Carregado via JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Progresso</h6>
                    <div class="mb-2">
                        <small>Respondidas:</small>
                        <strong><span id="questoesRespondidas">0</span>/<span id="questoesTotais2">0</span></strong>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const simuladoId = <?php echo $simulado_id; ?>;
const tentativaId = <?php echo $tentativa_id; ?>;

let simulado = null;
let questoes = [];
let questaoAtualIndex = 0;
let respostas = {};
let tempoInicio = Date.now();
let timerInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    carregarSimulado();
});

async function carregarSimulado() {
    try {
        const response = await fetch(`api/simulados.php?action=detalhes&id=${simuladoId}`);
        simulado = await response.json();

        if (simulado.error) {
            alert('Erro: ' + simulado.error);
            window.location.href = 'simulados.php';
            return;
        }

        questoes = simulado.questoes;

        document.getElementById('tituloSimulado').textContent = simulado.titulo;
        document.getElementById('totalQuestoes').textContent = questoes.length;
        document.getElementById('questoesTotais2').textContent = questoes.length;

        // Configurar timer se houver tempo limite
        if (simulado.tempo_limite > 0) {
            document.getElementById('timerCard').style.display = 'block';
            iniciarTimer(simulado.tempo_limite * 60);
        }

        // Criar navegação
        criarNavegacao();

        // Carregar primeira questão
        carregarQuestao(0);

    } catch (error) {
        console.error('Erro ao carregar simulado:', error);
        alert('Erro ao carregar simulado');
    }
}

function criarNavegacao() {
    const nav = document.getElementById('navegacaoQuestoes');
    nav.innerHTML = '';

    questoes.forEach((q, index) => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-secondary btn-questao';
        btn.textContent = index + 1;
        btn.onclick = () => carregarQuestao(index);
        btn.id = `navBtn${index}`;
        nav.appendChild(btn);
    });
}

function carregarQuestao(index) {
    questaoAtualIndex = index;
    const questao = questoes[index];

    document.getElementById('questaoAtual').textContent = index + 1;

    // Atualizar navegação
    document.querySelectorAll('.btn-questao').forEach((btn, i) => {
        btn.classList.remove('atual');
        if (i === index) btn.classList.add('atual');
    });

    // Montar HTML da questão
    let html = `
        <div class="mb-4">
            <h5 class="mb-3">Questão ${questao.numero_questao}</h5>
            <p class="lead">${questao.enunciado}</p>
        </div>

        <div id="alternativas">
            ${criarAlternativa('A', questao.alternativa_a, index)}
            ${criarAlternativa('B', questao.alternativa_b, index)}
            ${criarAlternativa('C', questao.alternativa_c, index)}
            ${criarAlternativa('D', questao.alternativa_d, index)}
            ${questao.alternativa_e ? criarAlternativa('E', questao.alternativa_e, index) : ''}
        </div>
    `;

    document.getElementById('areaQuestao').innerHTML = html;
    document.getElementById('areaAnaliseIA').style.display = 'none';

    // Restaurar resposta se já foi respondida
    if (respostas[questao.id]) {
        marcarAlternativa(respostas[questao.id].resposta);
    }

    // Atualizar botões
    document.getElementById('btnAnterior').disabled = index === 0;
    document.getElementById('btnResponder').disabled = !respostas[questao.id];
    document.getElementById('btnProxima').style.display = 'none';
    document.getElementById('btnFinalizar').style.display = 'none';

    if (respostas[questao.id]?.respondida) {
        mostrarResultadoQuestao(respostas[questao.id]);
    }

    tempoInicio = Date.now();
}

function criarAlternativa(letra, texto, index) {
    return `
        <div class="alternativa-item" onclick="selecionarAlternativa('${letra}', ${index})">
            <input type="radio" name="resposta${index}" value="${letra}" id="alt${letra}${index}">
            <label for="alt${letra}${index}" class="mb-0" style="cursor: pointer;">
                <strong>${letra})</strong> ${texto}
            </label>
        </div>
    `;
}

function selecionarAlternativa(letra, index) {
    document.getElementById(`alt${letra}${index}`).checked = true;
    marcarAlternativa(letra);
    document.getElementById('btnResponder').disabled = false;
}

function marcarAlternativa(letra) {
    document.querySelectorAll('.alternativa-item').forEach(item => {
        item.classList.remove('selecionada');
    });

    const input = document.querySelector(`input[value="${letra}"]`);
    if (input) {
        input.checked = true;
        input.closest('.alternativa-item').classList.add('selecionada');
    }
}

async function responderQuestao() {
    const questao = questoes[questaoAtualIndex];
    const respostaSelecionada = document.querySelector('input[name="resposta' + questaoAtualIndex + '"]:checked');

    if (!respostaSelecionada) {
        alert('Selecione uma alternativa');
        return;
    }

    const tempoResposta = Math.floor((Date.now() - tempoInicio) / 1000);

    try {
        const response = await fetch('api/simulados.php?action=responder', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                simulado_id: simuladoId,
                questao_id: questao.id,
                resposta: respostaSelecionada.value,
                tempo_resposta: tempoResposta
            })
        });

        const result = await response.json();

        if (result.success) {
            respostas[questao.id] = {
                resposta: respostaSelecionada.value,
                correta: result.correta,
                resposta_correta: result.resposta_correta,
                explicacao: result.explicacao,
                resposta_id: result.resposta_id,
                respondida: true
            };

            mostrarResultadoQuestao(result);
            atualizarNavegacao(questaoAtualIndex, true);
            atualizarEstatisticas();

            // Se errou, carregar análise da IA
            if (!result.correta) {
                carregarAnaliseIA(result.resposta_id);
            }
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao responder questão');
    }
}

function mostrarResultadoQuestao(result) {
    document.querySelectorAll('.alternativa-item').forEach(item => {
        const input = item.querySelector('input');
        if (input.value === result.resposta_correta) {
            item.classList.add('correta');
        }
        if (input.value === result.resposta && !result.correta) {
            item.classList.add('incorreta');
        }
        input.disabled = true;
        item.onclick = null;
    });

    document.getElementById('btnResponder').style.display = 'none';

    if (questaoAtualIndex < questoes.length - 1) {
        document.getElementById('btnProxima').style.display = 'block';
    } else {
        document.getElementById('btnFinalizar').style.display = 'block';
    }
}

async function carregarAnaliseIA(respostaId) {
    const areaAnalise = document.getElementById('areaAnaliseIA');
    areaAnalise.innerHTML = `
        <div class="analise-ia">
            <div class="d-flex align-items-center mb-3">
                <div class="spinner-border text-light me-3" role="status"></div>
                <h5 class="mb-0">Gerando análise personalizada...</h5>
            </div>
        </div>
    `;
    areaAnalise.style.display = 'block';

    try {
        const response = await fetch('api/analise_questao_ia.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ resposta_id: respostaId })
        });

        const result = await response.json();

        if (result.success) {
            areaAnalise.innerHTML = `
                <div class="analise-ia">
                    <h5 class="mb-3">
                        <i class="bi bi-lightbulb"></i> Análise do Professor
                        ${result.cached ? '<span class="badge bg-light text-dark ms-2">Cache</span>' : ''}
                    </h5>
                    <div style="white-space: pre-wrap;">${result.analise}</div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erro ao carregar análise:', error);
        areaAnalise.innerHTML = `
            <div class="alert alert-warning">
                Não foi possível carregar a análise. Tente novamente mais tarde.
            </div>
        `;
    }
}

function atualizarNavegacao(index, respondida) {
    const btn = document.getElementById(`navBtn${index}`);
    if (respondida) {
        btn.classList.add('respondida');
    }
}

function atualizarEstatisticas() {
    const respondidas = Object.keys(respostas).length;
    document.getElementById('questoesRespondidas').textContent = respondidas;

    const progresso = (respondidas / questoes.length) * 100;
    document.getElementById('progressBar').style.width = progresso + '%';
}

function proximaQuestao() {
    if (questaoAtualIndex < questoes.length - 1) {
        carregarQuestao(questaoAtualIndex + 1);
    }
}

function questaoAnterior() {
    if (questaoAtualIndex > 0) {
        carregarQuestao(questaoAtualIndex - 1);
    }
}

async function finalizarSimulado() {
    if (!confirm('Deseja realmente finalizar o simulado?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('tentativa_id', tentativaId);

        const response = await fetch('api/simulados.php?action=finalizar', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(`Simulado finalizado!\n\nNota: ${result.nota}%\nAcertos: ${result.corretas}/${result.total}`);
            window.location.href = `resultado_simulado.php?tentativa=${tentativaId}`;
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao finalizar simulado');
    }
}

function iniciarTimer(segundos) {
    let tempoRestante = segundos;

    timerInterval = setInterval(() => {
        tempoRestante--;

        const minutos = Math.floor(tempoRestante / 60);
        const segs = tempoRestante % 60;

        document.getElementById('timer').textContent =
            `${String(minutos).padStart(2, '0')}:${String(segs).padStart(2, '0')}`;

        if (tempoRestante <= 0) {
            clearInterval(timerInterval);
            alert('Tempo esgotado!');
            finalizarSimulado();
        }
    }, 1000);
}

// Prevenir saída acidental
window.addEventListener('beforeunload', function(e) {
    if (Object.keys(respostas).length > 0 && !confirm('Tem certeza que deseja sair? Seu progresso será salvo.')) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
