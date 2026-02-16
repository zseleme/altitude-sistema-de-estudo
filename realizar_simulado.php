<?php
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

<!-- Modal Texto de Apoio -->
<div id="modalTextoApoio" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl" style="max-height: 80vh; overflow-y: auto;">
        <div class="bg-indigo-600 text-white p-6 rounded-t-2xl flex items-center justify-between">
            <h3 class="text-xl font-bold">
                <i class="fas fa-book-open mr-2"></i>
                Texto de Apoio
            </h3>
            <button onclick="fecharModalTextoApoio()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div id="conteudoTextoApoio" class="text-gray-800 leading-relaxed" style="white-space: pre-wrap;"></div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end bg-gray-50">
            <button onclick="fecharModalTextoApoio()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="modalConfirmar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full transform transition-all">
        <div class="p-6">
            <div class="text-center mb-4">
                <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-question-circle text-3xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Finalizar Simulado?</h3>
                <p class="text-gray-600">Você tem certeza que deseja finalizar o simulado? Esta ação não pode ser desfeita.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="fecharModalConfirmar()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Cancelar
                </button>
                <button onclick="confirmarFinalizacao()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Tempo Esgotado -->
<div id="modalTempoEsgotado" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full transform transition-all">
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-6 rounded-t-2xl text-center">
            <div class="mb-4">
                <i class="fas fa-clock text-6xl opacity-90"></i>
            </div>
            <h2 class="text-2xl font-bold">Tempo Esgotado!</h2>
        </div>
        <div class="p-6 text-center">
            <p class="text-gray-600 mb-4">O tempo do simulado acabou. Seu progresso será salvo automaticamente.</p>
            <button onclick="fecharModalTempoEsgotado()"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                Continuar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Resultado -->
<div id="modalResultado" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-scale-in">
        <!-- Header do Modal -->
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 text-white p-6 rounded-t-2xl text-center">
            <div class="mb-4">
                <i class="fas fa-trophy text-6xl opacity-90"></i>
            </div>
            <h2 class="text-2xl font-bold">Simulado Finalizado!</h2>
        </div>

        <!-- Corpo do Modal -->
        <div class="p-6">
            <!-- Nota -->
            <div class="text-center mb-6">
                <div class="text-5xl font-bold mb-2" id="modalNota">0%</div>
                <p class="text-gray-600">Sua pontuação</p>
            </div>

            <!-- Estatísticas -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600" id="modalCorretas">0</div>
                    <div class="text-sm text-green-700">Acertos</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600" id="modalErradas">0</div>
                    <div class="text-sm text-red-700">Erros</div>
                </div>
            </div>

            <!-- Mensagem de Desempenho -->
            <div id="modalMensagem" class="text-center mb-6 p-4 rounded-lg">
                <!-- Preenchido via JavaScript -->
            </div>

            <!-- Botões -->
            <div class="flex gap-3">
                <button onclick="window.location.href=\'simulados.php\'"
                    class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </button>
                <button onclick="window.location.href=\'resultado_simulado.php?tentativa=\'+tentativaId"
                    class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Ver Detalhes
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}
</style>

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
    let btnTextoApoio = \'\';
    if (questao.texto_apoio && questao.texto_apoio.trim() !== \'\') {
        btnTextoApoio = \'<button onclick="abrirModalTextoApoio()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 inline-flex items-center"><i class="fas fa-book-open mr-2"></i>Texto de Apoio</button>\';
    }

    const html = `
        <div class="mb-6">
            <div class="flex items-start justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-700">Questão ${questao.numero_questao}</h3>
                ${btnTextoApoio}
            </div>
            <p class="text-gray-900 text-base leading-relaxed">${questao.enunciado}</p>
        </div>

        <div class="space-y-3">
            ${criarAlternativa(\'A\', questao.alternativa_a, index)}
            ${criarAlternativa(\'B\', questao.alternativa_b, index)}
            ${questao.alternativa_c && questao.alternativa_c.trim() ? criarAlternativa(\'C\', questao.alternativa_c, index) : \'\'}
            ${questao.alternativa_d && questao.alternativa_d.trim() ? criarAlternativa(\'D\', questao.alternativa_d, index) : \'\'}
            ${questao.alternativa_e && questao.alternativa_e.trim() ? criarAlternativa(\'E\', questao.alternativa_e, index) : \'\'}
        </div>
    `;

    document.getElementById(\'areaQuestao\').innerHTML = html;
    document.getElementById(\'areaAnaliseIA\').style.display = \'none\';

    if (respostas[questao.id]) {
        marcarAlternativa(respostas[questao.id].resposta);
    }

    document.getElementById(\'btnAnterior\').disabled = index === 0;
    document.getElementById(\'btnProxima\').style.display = \'none\';
    document.getElementById(\'btnFinalizar\').style.display = \'none\';

    if (respostas[questao.id]?.respondida) {
        // Questão já foi respondida - mostrar resultado
        document.getElementById(\'btnResponder\').style.display = \'none\';
        mostrarResultadoQuestao(respostas[questao.id]);
    } else {
        // Questão não foi respondida - mostrar botão responder
        document.getElementById(\'btnResponder\').style.display = \'inline-block\';
        document.getElementById(\'btnResponder\').disabled = !respostas[questao.id];
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
        } else {
            alert(\'Erro ao processar resposta: \' + (result.error || \'Erro desconhecido\'));
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

function finalizarSimulado() {
    document.getElementById(\'modalConfirmar\').classList.remove(\'hidden\');
}

function fecharModalConfirmar() {
    document.getElementById(\'modalConfirmar\').classList.add(\'hidden\');
}

async function confirmarFinalizacao() {
    fecharModalConfirmar();

    try {
        const formData = new FormData();
        formData.append(\'tentativa_id\', tentativaId);

        const response = await fetch(\'api/simulados.php?action=finalizar\', {
            method: \'POST\',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            mostrarModalResultado(result);
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao finalizar simulado\');
    }
}

function mostrarModalResultado(result) {
    const nota = parseFloat(result.nota);
    const corretas = result.corretas;
    const total = result.total;
    const erradas = total - corretas;

    // Preencher dados do modal
    document.getElementById(\'modalNota\').textContent = nota.toFixed(1) + \'%\';
    document.getElementById(\'modalCorretas\').textContent = corretas;
    document.getElementById(\'modalErradas\').textContent = erradas;

    // Mensagem personalizada baseada no desempenho
    const modalMensagem = document.getElementById(\'modalMensagem\');
    if (nota >= 70) {
        modalMensagem.className = \'text-center mb-6 p-4 rounded-lg bg-green-50 border-2 border-green-200\';
        modalMensagem.innerHTML = `
            <div class="text-green-700">
                <i class="fas fa-star text-2xl mb-2"></i>
                <p class="font-bold text-lg">Parabéns! Excelente desempenho!</p>
                <p class="text-sm">Você está no caminho certo. Continue assim!</p>
            </div>
        `;
        // Mudar ícone do header para estrela
        document.querySelector(\'#modalResultado .fa-trophy\').className = \'fas fa-star text-6xl opacity-90\';
    } else if (nota >= 50) {
        modalMensagem.className = \'text-center mb-6 p-4 rounded-lg bg-yellow-50 border-2 border-yellow-200\';
        modalMensagem.innerHTML = `
            <div class="text-yellow-700">
                <i class="fas fa-thumbs-up text-2xl mb-2"></i>
                <p class="font-bold text-lg">Bom trabalho!</p>
                <p class="text-sm">Você está progredindo. Revise os erros para melhorar ainda mais.</p>
            </div>
        `;
        document.querySelector(\'#modalResultado .fa-trophy\').className = \'fas fa-thumbs-up text-6xl opacity-90\';
    } else {
        modalMensagem.className = \'text-center mb-6 p-4 rounded-lg bg-orange-50 border-2 border-orange-200\';
        modalMensagem.innerHTML = `
            <div class="text-orange-700">
                <i class="fas fa-book-reader text-2xl mb-2"></i>
                <p class="font-bold text-lg">Continue estudando!</p>
                <p class="text-sm">Revise o conteúdo e pratique mais. Você vai conseguir!</p>
            </div>
        `;
        document.querySelector(\'#modalResultado .fa-trophy\').className = \'fas fa-book-reader text-6xl opacity-90\';
    }

    // Mostrar modal
    document.getElementById(\'modalResultado\').classList.remove(\'hidden\');
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
            mostrarModalTempoEsgotado();
        }
    }, 1000);
}

function mostrarModalTempoEsgotado() {
    document.getElementById(\'modalTempoEsgotado\').classList.remove(\'hidden\');
}

function fecharModalTempoEsgotado() {
    document.getElementById(\'modalTempoEsgotado\').classList.add(\'hidden\');
    confirmarFinalizacao();
}

function abrirModalTextoApoio() {
    const questao = questoes[questaoAtualIndex];
    const conteudo = questao.texto_apoio || \'Sem texto de apoio disponível.\';
    document.getElementById(\'conteudoTextoApoio\').textContent = conteudo;
    document.getElementById(\'modalTextoApoio\').classList.remove(\'hidden\');
}

function fecharModalTextoApoio() {
    document.getElementById(\'modalTextoApoio\').classList.add(\'hidden\');
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
