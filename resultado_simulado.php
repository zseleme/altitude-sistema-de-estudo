<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$tentativa_id = $_GET['tentativa'] ?? 0;

$content = '
<div class="max-w-7xl mx-auto">
    <!-- Loading -->
    <div id="loadingArea" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-6xl text-blue-600 mb-4"></i>
        <p class="text-gray-600">Carregando resultado...</p>
    </div>

    <!-- Resultado -->
    <div id="resultadoArea" style="display: none;">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2" id="tituloSimulado"></h1>
            <p class="text-gray-600" id="descricaoSimulado"></p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold" id="notaFinal">0%</div>
                        <div class="text-sm opacity-90">Nota Final</div>
                    </div>
                    <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-star text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold" id="totalCorretas">0</div>
                        <div class="text-sm opacity-90">Acertos</div>
                    </div>
                    <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold" id="totalErradas">0</div>
                        <div class="text-sm opacity-90">Erros</div>
                    </div>
                    <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-times-circle text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold" id="tempoTotal">-</div>
                        <div class="text-sm opacity-90">Tempo</div>
                    </div>
                    <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Análise de Desempenho -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                Análise de Desempenho
            </h2>
            <div class="w-full bg-gray-200 rounded-full h-8 mb-4 overflow-hidden">
                <div class="bg-green-600 h-8 flex items-center justify-center text-white font-semibold text-sm transition-all duration-500" id="progressCorretas" style="width: 0%"></div>
            </div>
            <div id="analiseGeral"></div>
        </div>

        <!-- Análise por IA -->
        <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl shadow-sm border border-purple-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-robot text-purple-600 mr-2"></i>
                    Orientações de Estudo Personalizadas (IA)
                </h2>
                <button id="btnGerarAnaliseIA" onclick="gerarAnaliseIA()" class="hidden px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors inline-flex items-center">
                    <i class="fas fa-sparkles mr-2"></i>
                    Gerar Análise
                </button>
            </div>

            <!-- Loading da Análise -->
            <div id="loadingAnaliseIA" class="hidden text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-purple-600 mb-3"></i>
                <p class="text-gray-600">Analisando seu desempenho...</p>
            </div>

            <!-- Conteúdo da Análise -->
            <div id="conteudoAnaliseIA" class="prose max-w-none text-gray-800">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <style>
            #conteudoAnaliseIA h3 {
                font-size: 1.125rem;
                font-weight: 700;
                color: #1f2937;
                margin-top: 1rem;
                margin-bottom: 0.5rem;
            }
            #conteudoAnaliseIA h4 {
                font-size: 1rem;
                font-weight: 600;
                color: #374151;
                margin-top: 0.75rem;
                margin-bottom: 0.5rem;
            }
            #conteudoAnaliseIA ul {
                list-style-type: disc;
                margin-left: 1.5rem;
                margin-bottom: 1rem;
            }
            #conteudoAnaliseIA li {
                margin-bottom: 0.25rem;
                margin-left: 1rem;
            }
            #conteudoAnaliseIA p {
                margin-bottom: 0.75rem;
                line-height: 1.6;
            }
            #conteudoAnaliseIA strong {
                font-weight: 600;
                color: #111827;
            }
        </style>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-wrap gap-3">
                <button onclick="filtrarQuestoes(\'todas\')" class="filtro-btn px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Todas
                </button>
                <button onclick="filtrarQuestoes(\'corretas\')" class="filtro-btn px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    Apenas Corretas
                </button>
                <button onclick="filtrarQuestoes(\'incorretas\')" class="filtro-btn px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    Apenas Incorretas
                </button>
            </div>
        </div>

        <!-- Revisão Detalhada -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-list-check text-blue-600 mr-2"></i>
                    Revisão Detalhada
                </h2>
            </div>
            <div class="p-6" id="revisaoQuestoes">
                <!-- Carregado via JS -->
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="simulados.php" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar aos Simulados
            </a>
            <button onclick="window.print()" class="inline-flex items-center justify-center px-6 py-3 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-print mr-2"></i>
                Imprimir
            </button>
        </div>
    </div>
</div>

<script>
const tentativaId = ' . $tentativa_id . ';
let resultado = null;
let filtroAtual = \'todas\';

document.addEventListener(\'DOMContentLoaded\', carregarResultado);

async function carregarResultado() {
    try {
        const response = await fetch(`api/simulados.php?action=resultado&tentativa_id=${tentativaId}`);
        resultado = await response.json();

        if (resultado.error) {
            alert(\'Erro: \' + resultado.error);
            window.location.href = \'simulados.php\';
            return;
        }

        exibirResultado();
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao carregar resultado\');
    }
}

function exibirResultado() {
    document.getElementById(\'tituloSimulado\').textContent = resultado.titulo;
    document.getElementById(\'descricaoSimulado\').textContent = resultado.descricao || \'\';
    document.getElementById(\'notaFinal\').textContent = resultado.nota.toFixed(1) + \'%\';

    const corretas = resultado.questoes_corretas;
    const total = resultado.questoes_totais;
    const erradas = total - corretas;

    document.getElementById(\'totalCorretas\').textContent = corretas;
    document.getElementById(\'totalErradas\').textContent = erradas;

    if (resultado.data_inicio && resultado.data_fim) {
        const inicio = new Date(resultado.data_inicio);
        const fim = new Date(resultado.data_fim);
        const diffMin = Math.floor((fim - inicio) / 1000 / 60);
        document.getElementById(\'tempoTotal\').textContent = diffMin + \' min\';
    }

    const percentCorretas = (corretas / total) * 100;
    document.getElementById(\'progressCorretas\').style.width = percentCorretas + \'%\';
    document.getElementById(\'progressCorretas\').textContent = percentCorretas.toFixed(1) + \'%\';

    let analise = \'\';
    if (resultado.nota >= 70) {
        analise = \'<div class="bg-green-50 border-l-4 border-green-500 p-4 rounded"><p class="text-green-900"><strong>Parabéns!</strong> Excelente desempenho. Continue assim!</p></div>\';
    } else if (resultado.nota >= 50) {
        analise = \'<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded"><p class="text-yellow-900"><strong>Bom trabalho!</strong> Você está no caminho certo. Revise os erros e tente novamente.</p></div>\';
    } else {
        analise = \'<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded"><p class="text-red-900"><strong>Precisa melhorar.</strong> Revise o conteúdo e pratique mais. Não desista!</p></div>\';
    }
    document.getElementById(\'analiseGeral\').innerHTML = analise;

    exibirQuestoes();

    // Verificar se já existe análise IA
    if (resultado.analise_ia_desempenho) {
        exibirAnaliseIA(resultado.analise_ia_desempenho);
    } else {
        // Gerar análise IA automaticamente
        gerarAnaliseIA();
    }

    document.getElementById(\'loadingArea\').style.display = \'none\';
    document.getElementById(\'resultadoArea\').style.display = \'block\';
}

async function gerarAnaliseIA() {
    const loadingDiv = document.getElementById(\'loadingAnaliseIA\');
    const conteudoDiv = document.getElementById(\'conteudoAnaliseIA\');
    const btnGerar = document.getElementById(\'btnGerarAnaliseIA\');

    loadingDiv.classList.remove(\'hidden\');
    conteudoDiv.innerHTML = \'\';
    btnGerar.classList.add(\'hidden\');

    try {
        const formData = new FormData();
        formData.append(\'tentativa_id\', tentativaId);

        const response = await fetch(\'api/analise_desempenho_ia.php\', {
            method: \'POST\',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            exibirAnaliseIA(result.analise);
            // Atualizar resultado local
            resultado.analise_ia_desempenho = result.analise;
        } else {
            throw new Error(result.error || \'Erro ao gerar análise\');
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        conteudoDiv.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                <p class="text-yellow-900">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ${error.message || \'Não foi possível gerar a análise por IA no momento.\'}
                </p>
            </div>
        `;
        btnGerar.classList.remove(\'hidden\');
    } finally {
        loadingDiv.classList.add(\'hidden\');
    }
}

function exibirAnaliseIA(analiseMarkdown) {
    const conteudoDiv = document.getElementById(\'conteudoAnaliseIA\');
    const btnGerar = document.getElementById(\'btnGerarAnaliseIA\');

    // Converter Markdown básico para HTML
    let html = analiseMarkdown
        .replace(/^## (.*$)/gim, \'<h3 class="text-lg font-bold text-gray-900 mt-4 mb-2">$1</h3>\')
        .replace(/^### (.*$)/gim, \'<h4 class="text-md font-semibold text-gray-800 mt-3 mb-2">$1</h4>\')
        .replace(/\\*\\*(.+?)\\*\\*/g, \'<strong>$1</strong>\')
        .replace(/\\*(.+?)\\*/g, \'<em>$1</em>\')
        .replace(/^- (.+)$/gim, \'<li class="ml-4">$1</li>\')
        .replace(/\\n\\n/g, \'</p><p class="mb-3">\')
        .replace(/^(?!<[hl]|<li)/gim, \'<p class="mb-3">\');

    // Envolver listas em <ul>
    html = html.replace(/(<li.*?<\\/li>\\s*)+/g, match => `<ul class="list-disc ml-6 mb-4 space-y-1">${match}</ul>`);

    conteudoDiv.innerHTML = `<div class="text-gray-800">${html}</div>`;

    // Mostrar botão para regenerar
    btnGerar.innerHTML = \'<i class="fas fa-redo mr-2"></i>Gerar Nova Análise\';
    btnGerar.classList.remove(\'hidden\');
}

function exibirQuestoes() {
    const container = document.getElementById(\'revisaoQuestoes\');
    container.innerHTML = \'\';

    resultado.respostas.forEach((resp, index) => {
        if (filtroAtual === \'corretas\' && !resp.correta) return;
        if (filtroAtual === \'incorretas\' && resp.correta) return;

        const div = document.createElement(\'div\');
        div.className = `border-l-4 p-4 rounded-lg mb-4 ${resp.correta ? \'bg-green-50 border-green-500\' : \'bg-red-50 border-red-500\'}`;
        div.innerHTML = `
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4">
                <h3 class="font-bold text-gray-900 mb-2 sm:mb-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold mr-2 ${resp.correta ? \'bg-green-600 text-white\' : \'bg-red-600 text-white\'}">
                        ${resp.correta ? \'✓ Correta\' : \'✗ Incorreta\'}
                    </span>
                    Questão ${resp.numero_questao}
                </h3>
                ${resp.tempo_resposta ? `<span class="text-sm text-gray-600"><i class="fas fa-clock mr-1"></i>${resp.tempo_resposta}s</span>` : \'\'}
            </div>

            <p class="text-gray-900 font-medium mb-4">${resp.enunciado}</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                ${criarAlternativaRevisao(\'A\', resp.alternativa_a, resp)}
                ${criarAlternativaRevisao(\'B\', resp.alternativa_b, resp)}
                ${criarAlternativaRevisao(\'C\', resp.alternativa_c, resp)}
                ${criarAlternativaRevisao(\'D\', resp.alternativa_d, resp)}
                ${resp.alternativa_e ? criarAlternativaRevisao(\'E\', resp.alternativa_e, resp) : \'\'}
            </div>

            ${resp.explicacao ? `
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-3">
                    <p class="text-blue-900 font-semibold mb-2"><i class="fas fa-info-circle mr-1"></i> Explicação:</p>
                    <p class="text-blue-800">${resp.explicacao}</p>
                </div>
            ` : \'\'}

            ${!resp.correta && resp.analise_ia ? `
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
                    <p class="text-purple-900 font-semibold mb-2"><i class="fas fa-lightbulb mr-1"></i> Análise do Professor:</p>
                    <div class="text-purple-900 whitespace-pre-wrap">${resp.analise_ia}</div>
                </div>
            ` : \'\'}
        `;

        container.appendChild(div);
    });

    if (container.innerHTML === \'\') {
        container.innerHTML = \'<p class="text-center text-gray-500 py-8">Nenhuma questão encontrada com este filtro.</p>\';
    }
}

function criarAlternativaRevisao(letra, texto, resp) {
    let classes = \'p-3 rounded-lg border-2 \';
    let icone = \'\';

    if (letra === resp.resposta_correta) {
        classes += \'bg-green-100 border-green-500\';
        icone = \'<i class="fas fa-check-circle text-green-600 ml-2"></i>\';
    } else if (letra === resp.resposta_usuario) {
        classes += \'bg-red-100 border-red-500\';
        icone = \'<i class="fas fa-times-circle text-red-600 ml-2"></i>\';
    } else {
        classes += \'bg-gray-50 border-gray-200\';
    }

    return `<div class="${classes}"><strong>${letra})</strong> ${texto}${icone}</div>`;
}

function filtrarQuestoes(tipo) {
    filtroAtual = tipo;

    document.querySelectorAll(\'.filtro-btn\').forEach(btn => {
        btn.classList.remove(\'bg-blue-600\', \'text-white\');
        btn.classList.add(\'bg-gray-200\', \'text-gray-700\');
    });

    event.target.classList.remove(\'bg-gray-200\', \'text-gray-700\');
    event.target.classList.add(\'bg-blue-600\', \'text-white\');

    exibirQuestoes();
}
</script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Resultado do Simulado', $content, true, true);
?>
