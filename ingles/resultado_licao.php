<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$tentativaId = $_GET['tentativa_id'] ?? 0;

if (!$tentativaId) {
    header('Location: /ingles/licoes.php');
    exit;
}

require_once __DIR__ . '/../includes/layout.php';

renderLayout('Resultado da Lição', function() use ($tentativaId, $userId) {
    ?>

    <div class="max-w-6xl mx-auto">
        <!-- Loading State -->
        <div id="loading-result" class="text-center py-12">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-600 mt-4">Carregando resultados...</p>
        </div>

        <!-- Result Content -->
        <div id="result-content" class="hidden">
            <!-- Header com Nota -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-8 mb-8 text-white">
                <div class="text-center">
                    <h1 class="text-3xl font-bold mb-4">
                        <i class="fas fa-trophy"></i> Resultado da Lição
                    </h1>
                    <div id="lesson-title" class="text-xl mb-6"></div>
                    <div class="flex justify-center items-center space-x-8">
                        <div>
                            <div class="text-6xl font-bold" id="nota-final">0</div>
                            <div class="text-sm opacity-90">Nota Final</div>
                        </div>
                        <div class="text-4xl">|</div>
                        <div>
                            <div class="text-3xl font-bold" id="acertos">0/9</div>
                            <div class="text-sm opacity-90">Questões Corretas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estatísticas por Tipo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900">
                            <i class="fas fa-list-ul text-blue-600"></i> Múltipla Escolha
                        </h3>
                        <span id="stats-multipla" class="text-2xl font-bold text-blue-600">0/3</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="bar-multipla" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900">
                            <i class="fas fa-fill-drip text-purple-600"></i> Preencher Lacuna
                        </h3>
                        <span id="stats-lacuna" class="text-2xl font-bold text-purple-600">0/3</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="bar-lacuna" class="bg-purple-600 h-2 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900">
                            <i class="fas fa-pen text-green-600"></i> Escrita
                        </h3>
                        <span id="stats-escrita" class="text-2xl font-bold text-green-600">0/2</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="bar-escrita" class="bg-green-600 h-2 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Filtro de Questões -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-gray-700">Filtrar:</span>
                    <button onclick="filtrarQuestoes('todas')" id="btn-todas"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium">
                        Todas
                    </button>
                    <button onclick="filtrarQuestoes('corretas')" id="btn-corretas"
                        class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300">
                        <i class="fas fa-check text-green-600"></i> Apenas Corretas
                    </button>
                    <button onclick="filtrarQuestoes('incorretas')" id="btn-incorretas"
                        class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300">
                        <i class="fas fa-times text-red-600"></i> Apenas Incorretas
                    </button>
                </div>
            </div>

            <!-- Revisão de Questões -->
            <div id="questions-review" class="space-y-6 mb-8">
                <!-- Questões carregadas via JavaScript -->
            </div>

            <!-- Ações -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-wrap gap-4 justify-center">
                    <a href="/ingles/licoes.php"
                       class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-arrow-left"></i> Voltar às Lições
                    </a>
                    <button id="btn-tentar-novamente"
                       class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                        <i class="fas fa-redo"></i> Tentar Novamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let tentativa = null;
        let respostas = [];
        let filtroAtual = 'todas';
        const tentativaId = <?php echo $tentativaId; ?>;

        document.addEventListener('DOMContentLoaded', async function() {
            await carregarResultado();
        });

        // Carregar resultado
        async function carregarResultado() {
            try {
                const response = await fetch(`/api/ingles_licoes.php?action=resultado&tentativa_id=${tentativaId}`);
                const result = await response.json();

                if (result.success) {
                    tentativa = result.tentativa;
                    respostas = result.respostas;

                    renderResultado();

                    document.getElementById('loading-result').classList.add('hidden');
                    document.getElementById('result-content').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar resultado: ' + result.message);
                    window.location.href = '/ingles/licoes.php';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao carregar resultado');
                window.location.href = '/ingles/licoes.php';
            }
        }

        // Renderizar resultado
        function renderResultado() {
            // Header
            document.getElementById('lesson-title').textContent = tentativa.titulo;
            document.getElementById('nota-final').textContent = Math.round(tentativa.nota);
            document.getElementById('acertos').textContent = `${tentativa.questoes_corretas}/${tentativa.questoes_totais}`;

            // Calcular estatísticas por tipo
            const stats = {
                multipla_escolha: { corretas: 0, total: 0 },
                preencher_lacuna: { corretas: 0, total: 0 },
                escrita: { corretas: 0, total: 0 }
            };

            respostas.forEach(r => {
                if (stats[r.tipo_questao]) {
                    stats[r.tipo_questao].total++;
                    if (r.correta) {
                        stats[r.tipo_questao].corretas++;
                    }
                }
            });

            // Atualizar estatísticas
            document.getElementById('stats-multipla').textContent =
                `${stats.multipla_escolha.corretas}/${stats.multipla_escolha.total}`;
            document.getElementById('bar-multipla').style.width =
                stats.multipla_escolha.total > 0 ? (stats.multipla_escolha.corretas / stats.multipla_escolha.total * 100) + '%' : '0%';

            document.getElementById('stats-lacuna').textContent =
                `${stats.preencher_lacuna.corretas}/${stats.preencher_lacuna.total}`;
            document.getElementById('bar-lacuna').style.width =
                stats.preencher_lacuna.total > 0 ? (stats.preencher_lacuna.corretas / stats.preencher_lacuna.total * 100) + '%' : '0%';

            document.getElementById('stats-escrita').textContent =
                `${stats.escrita.corretas}/${stats.escrita.total}`;
            document.getElementById('bar-escrita').style.width =
                stats.escrita.total > 0 ? (stats.escrita.corretas / stats.escrita.total * 100) + '%' : '0%';

            // Renderizar questões
            renderQuestoes();

            // Configurar botão de tentar novamente
            document.getElementById('btn-tentar-novamente').onclick = function() {
                window.location.href = `/ingles/realizar_licao.php?id=${tentativa.licao_id}`;
            };
        }

        // Filtrar questões
        function filtrarQuestoes(filtro) {
            filtroAtual = filtro;

            // Atualizar botões
            document.getElementById('btn-todas').className =
                filtro === 'todas' ? 'px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium' : 'px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300';
            document.getElementById('btn-corretas').className =
                filtro === 'corretas' ? 'px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium' : 'px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300';
            document.getElementById('btn-incorretas').className =
                filtro === 'incorretas' ? 'px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium' : 'px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300';

            renderQuestoes();
        }

        // Renderizar questões
        function renderQuestoes() {
            const container = document.getElementById('questions-review');
            container.innerHTML = '';

            let questoesFiltradas = respostas;
            if (filtroAtual === 'corretas') {
                questoesFiltradas = respostas.filter(r => r.correta);
            } else if (filtroAtual === 'incorretas') {
                questoesFiltradas = respostas.filter(r => !r.correta);
            }

            if (questoesFiltradas.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-filter text-4xl mb-3"></i>
                        <p>Nenhuma questão encontrada com este filtro</p>
                    </div>
                `;
                return;
            }

            questoesFiltradas.forEach((r, index) => {
                const card = document.createElement('div');
                card.className = `bg-white rounded-lg shadow-md p-6 border-l-4 ${r.correta ? 'border-green-500' : 'border-red-500'}`;

                let html = `
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="text-lg font-bold text-gray-900">Questão ${r.numero_questao}</span>
                                <span class="text-xs px-3 py-1 rounded-full ${getTipoBadgeClass(r.tipo_questao)}">
                                    ${getTipoLabel(r.tipo_questao)}
                                </span>
                            </div>
                            <p class="text-gray-700">${escapeHtml(r.enunciado)}</p>
                            ${r.contexto ? `<p class="text-gray-600 text-sm italic mt-2">${escapeHtml(r.contexto)}</p>` : ''}
                        </div>
                        <div class="ml-4">
                            <span class="text-3xl ${r.correta ? 'text-green-600' : 'text-red-600'}">
                                <i class="fas fa-${r.correta ? 'check-circle' : 'times-circle'}"></i>
                            </span>
                        </div>
                    </div>
                `;

                // Renderizar baseado no tipo
                if (r.tipo_questao === 'multipla_escolha') {
                    html += renderMultipleChoiceReview(r);
                } else if (r.tipo_questao === 'preencher_lacuna') {
                    html += renderFillInBlankReview(r);
                } else if (r.tipo_questao === 'escrita') {
                    html += renderWritingReview(r);
                }

                card.innerHTML = html;
                container.appendChild(card);
            });
        }

        // Renderizar revisão de múltipla escolha
        function renderMultipleChoiceReview(r) {
            let html = '<div class="mt-4 space-y-2">';

            ['A', 'B', 'C', 'D'].forEach(letra => {
                const alternativa = r['alternativa_' + letra.toLowerCase()];
                if (!alternativa) return;

                const isUserAnswer = r.resposta_multipla === letra;
                const isCorrect = r.resposta_correta_multipla === letra;

                let className = 'p-3 rounded-lg border-2 ';
                if (isCorrect) {
                    className += 'border-green-500 bg-green-50';
                } else if (isUserAnswer && !isCorrect) {
                    className += 'border-red-500 bg-red-50';
                } else {
                    className += 'border-gray-200';
                }

                html += `
                    <div class="${className}">
                        <span class="font-semibold">${letra})</span>
                        <span class="ml-2">${escapeHtml(alternativa)}</span>
                        ${isCorrect ? '<i class="fas fa-check text-green-600 float-right"></i>' : ''}
                        ${isUserAnswer && !isCorrect ? '<i class="fas fa-times text-red-600 float-right"></i>' : ''}
                    </div>
                `;
            });

            html += '</div>';

            if (r.explicacao) {
                html += `
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <p class="text-sm font-semibold text-blue-900 mb-1">
                            <i class="fas fa-lightbulb"></i> Explicação:
                        </p>
                        <p class="text-sm text-blue-800">${escapeHtml(r.explicacao)}</p>
                    </div>
                `;
            }

            return html;
        }

        // Renderizar revisão de preencher lacuna
        function renderFillInBlankReview(r) {
            const parts = r.texto_com_lacuna.split('____');

            let html = `
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Sua resposta:</p>
                    <p class="text-lg">
                        ${parts[0] || ''}
                        <span class="px-3 py-1 rounded ${r.correta ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900'} font-semibold">
                            ${escapeHtml(r.resposta_lacuna)}
                        </span>
                        ${parts[1] || ''}
                    </p>
                </div>
            `;

            if (!r.correta) {
                const aceitasArray = r.respostas_aceitas || [r.resposta_correta_lacuna];
                html += `
                    <div class="mt-3 p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
                        <p class="text-sm font-semibold text-green-900 mb-1">
                            <i class="fas fa-check"></i> Respostas aceitas:
                        </p>
                        <p class="text-sm text-green-800">${aceitasArray.join(', ')}</p>
                    </div>
                `;
            }

            if (r.explicacao) {
                html += `
                    <div class="mt-3 p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <p class="text-sm font-semibold text-blue-900 mb-1">
                            <i class="fas fa-lightbulb"></i> Explicação:
                        </p>
                        <p class="text-sm text-blue-800">${escapeHtml(r.explicacao)}</p>
                    </div>
                `;
            }

            return html;
        }

        // Renderizar revisão de escrita
        function renderWritingReview(r) {
            let html = `
                <div class="mt-4 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-2">Seu texto:</p>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(r.resposta_escrita)}</p>
                        </div>
                    </div>
            `;

            if (r.analise_ia) {
                const av = r.analise_ia;
                html += `
                    <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-blue-900">
                                <i class="fas fa-robot"></i> Avaliação da IA
                            </h4>
                            <span class="text-2xl font-bold ${av.pontuacao >= 70 ? 'text-green-600' : 'text-yellow-600'}">
                                ${av.pontuacao}/100
                            </span>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="font-semibold text-blue-900 mb-1">Feedback:</p>
                                <p class="text-blue-800">${escapeHtml(av.feedback)}</p>
                            </div>
                            ${av.pontos_fortes && av.pontos_fortes.length > 0 ? `
                                <div>
                                    <p class="font-semibold text-green-700 mb-1">
                                        <i class="fas fa-check"></i> Pontos Fortes:
                                    </p>
                                    <ul class="list-disc pl-5 text-blue-800">
                                        ${av.pontos_fortes.map(p => `<li>${escapeHtml(p)}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                            ${av.pontos_melhorar && av.pontos_melhorar.length > 0 ? `
                                <div>
                                    <p class="font-semibold text-yellow-700 mb-1">
                                        <i class="fas fa-exclamation-triangle"></i> Pontos a Melhorar:
                                    </p>
                                    <ul class="list-disc pl-5 text-blue-800">
                                        ${av.pontos_melhorar.map(p => `<li>${escapeHtml(p)}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                            ${av.sugestoes && av.sugestoes.length > 0 ? `
                                <div>
                                    <p class="font-semibold text-purple-700 mb-1">
                                        <i class="fas fa-lightbulb"></i> Sugestões:
                                    </p>
                                    <ul class="list-disc pl-5 text-blue-800">
                                        ${av.sugestoes.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }

            html += '</div>';
            return html;
        }

        // Helpers
        function getTipoBadgeClass(tipo) {
            const classes = {
                'multipla_escolha': 'bg-blue-100 text-blue-800',
                'preencher_lacuna': 'bg-purple-100 text-purple-800',
                'escrita': 'bg-green-100 text-green-800'
            };
            return classes[tipo] || 'bg-gray-100 text-gray-800';
        }

        function getTipoLabel(tipo) {
            const labels = {
                'multipla_escolha': 'Múltipla Escolha',
                'preencher_lacuna': 'Preencher Lacuna',
                'escrita': 'Escrita'
            };
            return labels[tipo] || tipo;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }
    </script>

    <?php
});
?>
