<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <div id="listaSimulados" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Carregado via JavaScript -->
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="hidden text-center py-12">
        <i class="fas fa-book-open text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum simulado disponível</h3>
        <p class="text-gray-500">Os simulados aparecerão aqui quando forem criados.</p>
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

    } catch (error) {
        console.error(\'Erro ao carregar simulados:\', error);
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
</script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Simulados', $content, true, true);
?>
