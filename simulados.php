<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$content = '
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Simulados</h1>
        <p class="text-gray-600">Teste seus conhecimentos e acompanhe seu progresso</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold" id="totalRealizados">0</div>
                    <div class="text-sm opacity-90">Realizados</div>
                </div>
                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold" id="mediaGeral">-</div>
                    <div class="text-sm opacity-90">Média Geral</div>
                </div>
                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold" id="totalDisponiveis">0</div>
                    <div class="text-sm opacity-90">Disponíveis</div>
                </div>
                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-book text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold" id="ultimaNota">-</div>
                    <div class="text-sm opacity-90">Última Nota</div>
                </div>
                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-star text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Simulados -->
    <div id="listaSimulados" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Carregado via JavaScript -->
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="hidden text-center py-12">
        <i class="fas fa-book-open text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum simulado disponível</h3>
        <p class="text-gray-500">Os simulados aparecerão aqui quando forem criados.</p>
    </div>

    <!-- Histórico de Tentativas -->
    <div id="historicoSection" class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-history mr-2"></i>
                Histórico de Tentativas
            </h2>
            <button id="toggleHistorico" onclick="toggleHistorico()"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors relative">
                <i class="fas fa-chevron-down mr-2"></i>
                Mostrar Histórico
                <span id="badgeHistorico" class="hidden absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center">0</span>
            </button>
        </div>

        <div id="historicoContent" class="hidden">
            <!-- Loading -->
            <div id="loadingHistorico" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                <p class="text-gray-600">Carregando histórico...</p>
            </div>

            <!-- Lista de Tentativas -->
            <div id="listaTentativas" class="hidden space-y-4">
                <!-- Carregado via JavaScript -->
            </div>

            <!-- Empty State Histórico -->
            <div id="emptyHistorico" class="hidden text-center py-12 bg-gray-50 rounded-xl">
                <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma tentativa ainda</h3>
                <p class="text-gray-500">Complete alguns simulados para ver seu histórico aqui.</p>
            </div>
        </div>
    </div>
</div>

<script>
let simulados = [];

document.addEventListener(\'DOMContentLoaded\', function() {
    carregarSimulados();
});

async function carregarSimulados() {
    try {
        const response = await fetch(\'api/simulados.php?action=listar\');
        simulados = await response.json();

        if (!simulados || simulados.length === 0) {
            document.getElementById(\'emptyState\').classList.remove(\'hidden\');
            document.getElementById(\'listaSimulados\').classList.add(\'hidden\');
            return;
        }

        const container = document.getElementById(\'listaSimulados\');
        container.innerHTML = \'\';

        let totalRealizados = 0;
        let somaNotas = 0;
        let countNotas = 0;
        let ultimaNota = null;

        simulados.forEach(sim => {
            if (sim.tentativas > 0) {
                totalRealizados++;
                if (sim.melhor_nota) {
                    somaNotas += parseFloat(sim.melhor_nota);
                    countNotas++;
                }
            }

            const card = document.createElement(\'div\');
            card.className = \'bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow\';
            card.innerHTML = `
                <!-- Header do Card -->
                <div class="h-32 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-file-alt text-5xl text-white opacity-90"></i>
                </div>

                <!-- Conteúdo -->
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">${sim.titulo}</h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">${sim.descricao || \'Teste seus conhecimentos neste simulado\'}</p>

                    <!-- Info Tags -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        ${sim.disciplina ? `
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-xs font-medium">
                                <i class="fas fa-book mr-1"></i>${sim.disciplina}
                            </span>
                        ` : \'\'}
                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-md text-xs font-medium">
                            <i class="fas fa-question-circle mr-1"></i>${sim.total_questoes || 0} questões
                        </span>
                        ${sim.tempo_limite > 0 ? `
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-md text-xs font-medium">
                                <i class="fas fa-clock mr-1"></i>${sim.tempo_limite} min
                            </span>
                        ` : \'\'}
                    </div>

                    <!-- Progresso -->
                    ${sim.tentativas > 0 ? `
                        <div class="bg-blue-50 rounded-lg p-3 mb-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-blue-700 font-medium">
                                    <i class="fas fa-history mr-1"></i>
                                    Tentativas: ${sim.tentativas}
                                </span>
                                ${sim.melhor_nota ? `
                                    <span class="text-blue-900 font-bold">
                                        Melhor: ${parseFloat(sim.melhor_nota).toFixed(1)}%
                                    </span>
                                ` : \'\'}
                            </div>
                        </div>
                    ` : \'\'}

                    <!-- Botão Ação -->
                    <button onclick="iniciarSimulado(${sim.id})" class="w-full bg-blue-600 text-white text-sm font-medium rounded-lg px-4 py-3 hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-play mr-2"></i>
                        ${sim.tentativas > 0 ? \'Refazer Simulado\' : \'Iniciar Simulado\'}
                    </button>
                </div>
            `;
            container.appendChild(card);
        });

        // Atualizar estatísticas
        document.getElementById(\'totalRealizados\').textContent = totalRealizados;
        document.getElementById(\'totalDisponiveis\').textContent = simulados.length;

        if (countNotas > 0) {
            const media = somaNotas / countNotas;
            document.getElementById(\'mediaGeral\').textContent = media.toFixed(1) + \'%\';
        }

        // Buscar última tentativa para preencher "Última Nota"
        buscarUltimaNota();

    } catch (error) {
        console.error(\'Erro ao carregar simulados:\', error);
    }
}

async function buscarUltimaNota() {
    try {
        const response = await fetch(\'api/simulados.php?action=minhas_tentativas\');
        const tentativas = await response.json();

        if (tentativas && tentativas.length > 0) {
            // A primeira tentativa é a mais recente (ORDER BY data_inicio DESC)
            const ultimaTentativa = tentativas[0];
            const nota = parseFloat(ultimaTentativa.nota);
            document.getElementById(\'ultimaNota\').textContent = nota.toFixed(1) + \'%\';

            // Atualizar badge do histórico
            const badgeElement = document.getElementById(\'badgeHistorico\');
            if (badgeElement) {
                badgeElement.textContent = tentativas.length;
                badgeElement.classList.remove(\'hidden\');
            }
        }
    } catch (error) {
        console.error(\'Erro ao buscar última nota:\', error);
    }
}

async function iniciarSimulado(simuladoId) {
    try {
        const formData = new FormData();
        formData.append(\'simulado_id\', simuladoId);

        const response = await fetch(\'api/simulados.php?action=iniciar\', {
            method: \'POST\',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = `realizar_simulado.php?id=${simuladoId}&tentativa=${result.tentativa_id}`;
        } else {
            alert(\'Erro ao iniciar simulado: \' + (result.error || \'Erro desconhecido\'));
        }
    } catch (error) {
        console.error(\'Erro:\', error);
        alert(\'Erro ao iniciar simulado\');
    }
}

let historicoCarregado = false;

async function toggleHistorico() {
    const content = document.getElementById(\'historicoContent\');
    const btn = document.getElementById(\'toggleHistorico\');
    const icon = btn.querySelector(\'i\');

    if (content.classList.contains(\'hidden\')) {
        content.classList.remove(\'hidden\');
        btn.innerHTML = \'<i class="fas fa-chevron-up mr-2"></i>Ocultar Histórico\';

        if (!historicoCarregado) {
            await carregarHistorico();
            historicoCarregado = true;
        }
    } else {
        content.classList.add(\'hidden\');
        btn.innerHTML = \'<i class="fas fa-chevron-down mr-2"></i>Mostrar Histórico\';
    }
}

async function carregarHistorico() {
    const loadingDiv = document.getElementById(\'loadingHistorico\');
    const listaDiv = document.getElementById(\'listaTentativas\');
    const emptyDiv = document.getElementById(\'emptyHistorico\');

    loadingDiv.classList.remove(\'hidden\');
    listaDiv.classList.add(\'hidden\');
    emptyDiv.classList.add(\'hidden\');

    try {
        const response = await fetch(\'api/simulados.php?action=minhas_tentativas\');
        const tentativas = await response.json();

        loadingDiv.classList.add(\'hidden\');

        if (!tentativas || tentativas.length === 0) {
            emptyDiv.classList.remove(\'hidden\');
            return;
        }

        listaDiv.innerHTML = \'\';
        tentativas.forEach(tentativa => {
            const nota = parseFloat(tentativa.nota);
            const dataInicio = new Date(tentativa.data_inicio);
            const dataFim = tentativa.data_fim ? new Date(tentativa.data_fim) : null;

            // Calcular tempo gasto
            let tempoGasto = \'-\';
            if (dataFim) {
                const diffMin = Math.floor((dataFim - dataInicio) / 1000 / 60);
                tempoGasto = diffMin + \' min\';
            }

            // Cor baseada na nota
            let corNota = \'text-gray-900\';
            let bgNota = \'bg-gray-100\';
            if (nota >= 70) {
                corNota = \'text-green-700\';
                bgNota = \'bg-green-100\';
            } else if (nota >= 50) {
                corNota = \'text-yellow-700\';
                bgNota = \'bg-yellow-100\';
            } else {
                corNota = \'text-red-700\';
                bgNota = \'bg-red-100\';
            }

            const card = document.createElement(\'div\');
            card.className = \'bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow\';
            card.innerHTML = `
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <!-- Info do Simulado -->
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 mb-2">${tentativa.titulo}</h3>
                            <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                                ${tentativa.disciplina ? `
                                    <span class="flex items-center">
                                        <i class="fas fa-book mr-1"></i>
                                        ${tentativa.disciplina}
                                    </span>
                                ` : \'\'}
                                <span class="flex items-center">
                                    <i class="fas fa-calendar mr-1"></i>
                                    ${dataInicio.toLocaleDateString(\'pt-BR\')}
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-clock mr-1"></i>
                                    ${tempoGasto}
                                </span>
                            </div>
                        </div>

                        <!-- Nota e Estatísticas -->
                        <div class="flex items-center gap-4">
                            <div class="text-center">
                                <div class="${bgNota} ${corNota} px-4 py-2 rounded-lg">
                                    <div class="text-2xl font-bold">${nota.toFixed(1)}%</div>
                                    <div class="text-xs opacity-80">Nota</div>
                                </div>
                            </div>

                            <div class="text-center">
                                <div class="text-sm text-gray-600 mb-1">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                    ${tentativa.questoes_corretas}
                                </div>
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-times-circle text-red-600"></i>
                                    ${tentativa.questoes_totais - tentativa.questoes_corretas}
                                </div>
                            </div>

                            <!-- Botão Ver Resultado -->
                            <a href="resultado_simulado.php?tentativa=${tentativa.id}"
                               class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                                <i class="fas fa-eye mr-2"></i>
                                Ver Detalhes
                            </a>
                        </div>
                    </div>
                </div>
            `;

            listaDiv.appendChild(card);
        });

        listaDiv.classList.remove(\'hidden\');

    } catch (error) {
        console.error(\'Erro ao carregar histórico:\', error);
        loadingDiv.classList.add(\'hidden\');
        emptyDiv.classList.remove(\'hidden\');
        emptyDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-600 mb-2">Erro ao carregar histórico</h3>
            <p class="text-gray-500">Tente novamente mais tarde.</p>
        `;
    }
}
</script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Simulados', $content, true, true);
?>
