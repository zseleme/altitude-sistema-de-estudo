<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$simulado_id = $_GET['id'] ?? 0;
$tentativa_id = $_GET['tentativa'] ?? 0;

$content = '
<style>
.alternativa-btn {
    cursor: pointer;
    transition: all 0.2s;
}
.alternativa-btn:hover:not(.correta):not(.incorreta) {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}
.alternativa-btn.selecionada {
    background-color: #dbeafe;
    border-color: #3b82f6;
}
.alternativa-btn.correta {
    background-color: #d1fae5;
    border-color: #10b981;
}
.alternativa-btn.incorreta {
    background-color: #fee2e2;
    border-color: #ef4444;
}
</style>

<div class="max-w-7xl mx-auto">
    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Área Principal (3 colunas) -->
        <div class="lg:col-span-3">
            <!-- Card da Questão -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <!-- Header -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold" id="tituloSimulado">Carregando...</h2>
                        <span class="bg-white/20 px-3 py-1 rounded-lg text-sm font-medium">
                            Questão <span id="questaoAtual">1</span> de <span id="totalQuestoes">0</span>
                        </span>
                    </div>
                </div>

                <!-- Corpo da Questão -->
                <div class="p-6" id="areaQuestao">
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
                        <p>Carregando questão...</p>
                    </div>
                </div>

                <!-- Footer com Botões -->
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between">
                        <button id="btnAnterior" onclick="questaoAnterior()" disabled
                            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center">
                            <i class="fas fa-chevron-left mr-2"></i>
                            Anterior
                        </button>

                        <button id="btnResponder" onclick="responderQuestao()" disabled
                            class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Responder
                        </button>

                        <button id="btnProxima" onclick="proximaQuestao()" style="display: none;"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                            Próxima
                            <i class="fas fa-chevron-right ml-2"></i>
                        </button>

                        <button id="btnFinalizar" onclick="finalizarSimulado()" style="display: none;"
                            class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors inline-flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            Finalizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Análise IA -->
            <div id="areaAnaliseIA" style="display: none;"></div>
        </div>

        <!-- Sidebar (1 coluna) -->
        <div class="lg:col-span-1">
            <!-- Timer -->
            <div id="timerCard" style="display: none;" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4 sticky top-20">
                <h3 class="text-sm font-medium text-gray-600 mb-2 text-center">Tempo Restante</h3>
                <div class="text-4xl font-bold text-gray-900 text-center" id="timer">00:00</div>
            </div>

            <!-- Navegação -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Navegação</h3>
                <div class="grid grid-cols-5 gap-2" id="navegacaoQuestoes">
                    <!-- Carregado via JS -->
                </div>
            </div>

            <!-- Progresso -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Progresso</h3>
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Respondidas:</span>
                    <strong><span id="questoesRespondidas">0</span>/<span id="questoesTotais2">0</span></strong>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" id="progressBar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const simuladoId = ' . $simulado_id . ';
const tentativaId = ' . $tentativa_id . ';

let simulado = null;
let questoes = [];
let questaoAtualIndex = 0;
let respostas = {};
let tempoInicio = Date.now();
let timerInterval = null;

document.addEventListener(\'DOMContentLoaded\', carregarSimulado);

async function carregarSimulado() {
    try {
        const response = await fetch(`api/simulados.php?action=detalhes&id=${simuladoId}`);
        simulado = await response.json();

        if (simulado.error) {
            alert(\'Erro: \' + simulado.error);
            window.location.href = \'simulados.php\';
            return;
        }

        questoes = simulado.questoes;
        document.getElementById(\'tituloSimulado\').textContent = simulado.titulo;
        document.getElementById(\'totalQuestoes\').textContent = questoes.length;
        document.getElementById(\'questoesTotais2\').textContent = questoes.length;

        if (simulado.tempo_limite > 0) {
            document.getElementById(\'timerCard\').style.display = \'block\';
            iniciarTimer(simulado.tempo_limite * 60);
        }

        criarNavegacao();
        carregarQuestao(0);
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao carregar simulado\');
    }
}

function criarNavegacao() {
    const nav = document.getElementById(\'navegacaoQuestoes\');
    nav.innerHTML = \'\';

    questoes.forEach((q, index) => {
        const btn = document.createElement(\'button\');
        btn.className = \'h-10 w-10 rounded-lg border-2 border-gray-300 hover:bg-gray-100 font-semibold text-sm transition-colors\';
        btn.textContent = index + 1;
        btn.onclick = () => carregarQuestao(index);
        btn.id = `navBtn${index}`;
        nav.appendChild(btn);
    });
}

function carregarQuestao(index) {
    questaoAtualIndex = index;
    const questao = questoes[index];

    document.getElementById(\'questaoAtual\').textContent = index + 1;

    // Atualizar navegação
    document.querySelectorAll(\'[id^="navBtn"]\').forEach((btn, i) => {
        btn.classList.remove(\'border-blue-600\', \'bg-blue-50\');
        btn.classList.add(\'border-gray-300\');
        if (i === index) {
            btn.classList.remove(\'border-gray-300\');
            btn.classList.add(\'border-blue-600\', \'bg-blue-50\');
        }
    });

    // Montar questão
    const html = `
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-700 mb-3">Questão ${questao.numero_questao}</h3>
            <p class="text-gray-900 text-base leading-relaxed">${questao.enunciado}</p>
        </div>

        <div class="space-y-3">
            ${criarAlternativa(\'A\', questao.alternativa_a, index)}
            ${criarAlternativa(\'B\', questao.alternativa_b, index)}
            ${criarAlternativa(\'C\', questao.alternativa_c, index)}
            ${criarAlternativa(\'D\', questao.alternativa_d, index)}
            ${questao.alternativa_e ? criarAlternativa(\'E\', questao.alternativa_e, index) : \'\'}
        </div>
    `;

    document.getElementById(\'areaQuestao\').innerHTML = html;
    document.getElementById(\'areaAnaliseIA\').style.display = \'none\';

    if (respostas[questao.id]) {
        marcarAlternativa(respostas[questao.id].resposta);
    }

    document.getElementById(\'btnAnterior\').disabled = index === 0;
    document.getElementById(\'btnResponder\').disabled = !respostas[questao.id];
    document.getElementById(\'btnProxima\').style.display = \'none\';
    document.getElementById(\'btnFinalizar\').style.display = \'none\';

    if (respostas[questao.id]?.respondida) {
        mostrarResultadoQuestao(respostas[questao.id]);
    }

    tempoInicio = Date.now();
}

function criarAlternativa(letra, texto, index) {
    return `
        <button type="button" onclick="selecionarAlternativa(\'${letra}\', ${index})"
            class="alternativa-btn w-full text-left p-4 border-2 border-gray-300 rounded-lg bg-white"
            id="alt${letra}${index}">
            <div class="flex items-start">
                <span class="font-bold text-gray-900 mr-3">${letra})</span>
                <span class="text-gray-800 flex-1">${texto}</span>
            </div>
        </button>
    `;
}

function selecionarAlternativa(letra, index) {
    const questao = questoes[index];
    if (respostas[questao.id]?.respondida) return;

    document.querySelectorAll(\'.alternativa-btn\').forEach(btn => {
        btn.classList.remove(\'selecionada\', \'border-blue-600\');
        btn.classList.add(\'border-gray-300\');
    });

    const btn = document.getElementById(`alt${letra}${index}`);
    btn.classList.remove(\'border-gray-300\');
    btn.classList.add(\'selecionada\', \'border-blue-600\');

    document.getElementById(\'btnResponder\').disabled = false;
    respostas[questao.id] = { resposta: letra, respondida: false };
}

function marcarAlternativa(letra) {
    const btn = document.getElementById(`alt${letra}${questaoAtualIndex}`);
    if (btn) {
        btn.classList.add(\'selecionada\', \'border-blue-600\');
    }
}

async function responderQuestao() {
    const questao = questoes[questaoAtualIndex];
    const resposta = respostas[questao.id];

    if (!resposta) {
        alert(\'Selecione uma alternativa\');
        return;
    }

    const tempoResposta = Math.floor((Date.now() - tempoInicio) / 1000);

    try {
        const response = await fetch(\'api/simulados.php?action=responder\', {
            method: \'POST\',
            headers: {\'Content-Type\': \'application/json\'},
            body: JSON.stringify({
                simulado_id: simuladoId,
                questao_id: questao.id,
                resposta: resposta.resposta,
                tempo_resposta: tempoResposta
            })
        });

        const result = await response.json();

        if (result.success) {
            respostas[questao.id] = {
                ...resposta,
                correta: result.correta,
                resposta_correta: result.resposta_correta,
                resposta_id: result.resposta_id,
                respondida: true
            };

            mostrarResultadoQuestao(result);
            atualizarNavegacao(questaoAtualIndex, true);
            atualizarEstatisticas();

            if (!result.correta) {
                carregarAnaliseIA(result.resposta_id);
            }
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao responder questão\');
    }
}

function mostrarResultadoQuestao(result) {
    document.querySelectorAll(\'.alternativa-btn\').forEach(btn => {
        const letra = btn.id.replace(`alt`, \'\').replace(questaoAtualIndex, \'\');
        btn.onclick = null;
        btn.style.cursor = \'default\';

        if (letra === result.resposta_correta) {
            btn.classList.add(\'correta\', \'border-green-600\');
            btn.classList.remove(\'border-gray-300\', \'border-blue-600\');
        }
        if (letra === result.resposta && !result.correta) {
            btn.classList.add(\'incorreta\', \'border-red-600\');
            btn.classList.remove(\'border-gray-300\', \'border-blue-600\');
        }
    });

    document.getElementById(\'btnResponder\').style.display = \'none\';

    if (questaoAtualIndex < questoes.length - 1) {
        document.getElementById(\'btnProxima\').style.display = \'inline-flex\';
    } else {
        document.getElementById(\'btnFinalizar\').style.display = \'inline-flex\';
    }
}

async function carregarAnaliseIA(respostaId) {
    const area = document.getElementById(\'areaAnaliseIA\');
    area.innerHTML = `
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center space-x-3 mb-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                <h3 class="font-bold">Gerando análise personalizada...</h3>
            </div>
        </div>
    `;
    area.style.display = \'block\';

    try {
        const response = await fetch(\'api/analise_questao_ia.php\', {
            method: \'POST\',
            headers: {\'Content-Type\': \'application/json\'},
            body: JSON.stringify({ resposta_id: respostaId })
        });

        const result = await response.json();

        if (result.success) {
            area.innerHTML = `
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                    <h3 class="font-bold text-lg mb-3 flex items-center">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Análise do Professor
                    </h3>
                    <div class="whitespace-pre-wrap leading-relaxed opacity-95">${result.analise}</div>
                </div>
            `;
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        area.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                <p class="text-yellow-700">Não foi possível carregar a análise.</p>
            </div>
        `;
    }
}

function atualizarNavegacao(index, respondida) {
    const btn = document.getElementById(`navBtn${index}`);
    if (respondida) {
        btn.classList.add(\'bg-blue-600\', \'text-white\', \'border-blue-600\');
        btn.classList.remove(\'border-gray-300\');
    }
}

function atualizarEstatisticas() {
    const respondidas = Object.values(respostas).filter(r => r.respondida).length;
    document.getElementById(\'questoesRespondidas\').textContent = respondidas;
    const progresso = (respondidas / questoes.length) * 100;
    document.getElementById(\'progressBar\').style.width = progresso + \'%\';
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
    if (!confirm(\'Deseja finalizar o simulado?\')) return;

    try {
        const formData = new FormData();
        formData.append(\'tentativa_id\', tentativaId);

        const response = await fetch(\'api/simulados.php?action=finalizar\', {
            method: \'POST\',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(`Simulado finalizado!\\n\\nNota: ${result.nota}%\\nAcertos: ${result.corretas}/${result.total}`);
            window.location.href = `resultado_simulado.php?tentativa=${tentativaId}`;
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao finalizar simulado\');
    }
}

function iniciarTimer(segundos) {
    let tempoRestante = segundos;

    timerInterval = setInterval(() => {
        tempoRestante--;

        const minutos = Math.floor(tempoRestante / 60);
        const segs = tempoRestante % 60;

        document.getElementById(\'timer\').textContent =
            `${String(minutos).padStart(2, \'0\')}:${String(segs).padStart(2, \'0\')}`;

        if (tempoRestante <= 0) {
            clearInterval(timerInterval);
            alert(\'Tempo esgotado!\');
            finalizarSimulado();
        }
    }, 1000);
}

window.addEventListener(\'beforeunload\', function(e) {
    if (Object.values(respostas).some(r => r.respondida)) {
        e.preventDefault();
        e.returnValue = \'\';
    }
});
</script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Realizar Simulado', $content, true, true);
?>
