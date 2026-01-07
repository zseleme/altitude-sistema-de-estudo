<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$licaoId = $_GET['id'] ?? 0;

if (!$licaoId) {
    header('Location: /ingles/licoes.php');
    exit;
}

require_once __DIR__ . '/../includes/layout.php';

$content = function() use ($licaoId, $userId) {
    ?>

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div id="lesson-header" class="mb-6">
            <!-- Carregado via JavaScript -->
        </div>

        <!-- Loading State -->
        <div id="loading-lesson" class="text-center py-12">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-600 mt-4">Carregando lição...</p>
        </div>

        <!-- Lesson Content -->
        <div id="lesson-content" class="hidden">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Content - Questão Atual -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <!-- Conteúdo de Apoio (mostrado antes de iniciar) -->
                        <div id="conteudo-apoio" class="mb-6">
                            <!-- Carregado via JavaScript -->
                        </div>

                        <!-- Área da Questão -->
                        <div id="questao-container">
                            <!-- Carregado dinamicamente via JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Navegação -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                        <h3 class="font-bold text-gray-900 mb-4">
                            <i class="fas fa-list-ol"></i> Questões
                        </h3>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Progresso</span>
                                <span id="progress-text">0/9</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Question Grid -->
                        <div id="question-nav-grid" class="grid grid-cols-3 gap-2 mb-4">
                            <!-- Botões carregados via JavaScript -->
                        </div>

                        <!-- Finalizar -->
                        <button id="btn-finalizar" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-semibold hidden">
                            <i class="fas fa-check"></i> Finalizar Lição
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Finalização -->
    <div id="modal-finalizar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-check-circle text-green-600"></i> Finalizar Lição
            </h3>
            <p class="text-gray-700 mb-6">
                Deseja finalizar esta lição? Você respondeu <span id="total-respondidas">0</span> de 9 questões.
            </p>
            <div class="flex justify-end space-x-3">
                <button onclick="fecharModalFinalizar()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">
                    Continuar Respondendo
                </button>
                <button id="btn-confirmar-finalizar" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    Finalizar
                </button>
            </div>
        </div>
    </div>

    <script>
        let licao = null;
        let questoes = [];
        let tentativaId = null;
        let currentQuestionIndex = 0;
        let respostasMap = {}; // {questaoId: {answered: true, correct: boolean}}

        const licaoId = <?php echo $licaoId; ?>;

        document.addEventListener('DOMContentLoaded', async function() {
            await carregarLicao();
            await iniciarTentativa();
            renderQuestion(0);
        });

        // Carregar lição
        async function carregarLicao() {
            try {
                const response = await fetch(`/api/ingles_licoes.php?action=detalhes&id=${licaoId}`);
                const result = await response.json();

                if (result.success) {
                    licao = result.licao;
                    questoes = result.questoes;

                    document.getElementById('lesson-header').innerHTML = `
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-book-open text-blue-600"></i> ${escapeHtml(licao.titulo)}
                        </h1>
                        <p class="text-gray-600 mt-2">${escapeHtml(licao.descricao || '')}</p>
                    `;

                    // Mostrar conteúdo de apoio se existir
                    if (licao.conteudo_apoio) {
                        document.getElementById('conteudo-apoio').innerHTML = `
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                                <h3 class="font-bold text-blue-900 mb-2">
                                    <i class="fas fa-lightbulb"></i> Conteúdo de Apoio
                                </h3>
                                <div class="text-blue-800 text-sm leading-relaxed whitespace-pre-wrap">${escapeHtml(licao.conteudo_apoio)}</div>
                            </div>
                        `;
                    }

                    renderQuestionNavGrid();

                    document.getElementById('loading-lesson').classList.add('hidden');
                    document.getElementById('lesson-content').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar lição: ' + result.message);
                    window.location.href = '/ingles/licoes.php';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao carregar lição');
                window.location.href = '/ingles/licoes.php';
            }
        }

        // Iniciar tentativa
        async function iniciarTentativa() {
            try {
                const formData = new FormData();
                formData.append('licao_id', licaoId);

                const response = await fetch('/api/ingles_licoes.php?action=iniciar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    tentativaId = result.tentativa_id;
                } else {
                    alert('Erro ao iniciar tentativa: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao iniciar tentativa');
            }
        }

        // Renderizar grid de navegação
        function renderQuestionNavGrid() {
            const grid = document.getElementById('question-nav-grid');
            grid.innerHTML = '';

            questoes.forEach((q, index) => {
                const btn = document.createElement('button');
                btn.className = 'w-full py-2 rounded-lg border-2 transition-all';
                btn.textContent = index + 1;
                btn.onclick = () => renderQuestion(index);
                btn.id = `nav-btn-${index}`;
                updateNavButtonStyle(btn, index);
                grid.appendChild(btn);
            });
        }

        // Atualizar estilo do botão de navegação
        function updateNavButtonStyle(btn, index) {
            const questaoId = questoes[index].id;
            const resposta = respostasMap[questaoId];

            if (index === currentQuestionIndex) {
                btn.className = 'w-full py-2 rounded-lg border-2 border-blue-600 bg-blue-600 text-white font-semibold';
            } else if (resposta && resposta.answered) {
                const colorClass = resposta.correct ? 'border-green-600 bg-green-50 text-green-800' : 'border-red-600 bg-red-50 text-red-800';
                btn.className = `w-full py-2 rounded-lg border-2 ${colorClass}`;
            } else {
                btn.className = 'w-full py-2 rounded-lg border-2 border-gray-300 hover:border-gray-400';
            }
        }

        // Atualizar todos os botões de navegação
        function updateAllNavButtons() {
            questoes.forEach((q, index) => {
                const btn = document.getElementById(`nav-btn-${index}`);
                if (btn) updateNavButtonStyle(btn, index);
            });
        }

        // Renderizar questão
        function renderQuestion(index) {
            currentQuestionIndex = index;
            const questao = questoes[index];
            const container = document.getElementById('questao-container');

            // Ocultar conteúdo de apoio após primeira questão
            if (index > 0) {
                document.getElementById('conteudo-apoio').classList.add('hidden');
            }

            let html = `
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">
                            Questão ${index + 1} de ${questoes.length}
                        </h2>
                        <span class="text-sm px-3 py-1 rounded-full ${getTipoBadgeClass(questao.tipo_questao)}">
                            ${getTipoLabel(questao.tipo_questao)}
                        </span>
                    </div>
                    <p class="text-gray-700 mb-4">${escapeHtml(questao.enunciado)}</p>
                    ${questao.contexto ? `<p class="text-gray-600 text-sm italic mb-4">${escapeHtml(questao.contexto)}</p>` : ''}
                </div>
            `;

            // Renderizar baseado no tipo
            switch (questao.tipo_questao) {
                case 'multipla_escolha':
                    html += renderMultipleChoice(questao);
                    break;
                case 'preencher_lacuna':
                    html += renderFillInBlank(questao);
                    break;
                case 'escrita':
                    html += renderWriting(questao);
                    break;
            }

            // Área de feedback
            html += `<div id="feedback-area" class="mt-6"></div>`;

            // Botões de navegação
            html += `
                <div class="flex justify-between mt-6 pt-4 border-t">
                    <button onclick="previousQuestion()" ${index === 0 ? 'disabled' : ''}
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </button>
                    <button onclick="nextQuestion()" ${index === questoes.length - 1 ? 'disabled' : ''}
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        Próxima <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            `;

            container.innerHTML = html;
            updateAllNavButtons();
            updateProgress();

            // Verificar se já foi respondida
            const resposta = respostasMap[questao.id];
            if (resposta && resposta.answered) {
                showExistingFeedback(questao, resposta);
            }
        }

        // Renderizar múltipla escolha
        function renderMultipleChoice(questao) {
            return `
                <div id="question-input" class="space-y-3">
                    ${['a', 'b', 'c', 'd'].map(letra => {
                        const alternativa = questao['alternativa_' + letra];
                        if (!alternativa) return '';
                        return `
                            <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 transition-colors">
                                <input type="radio" name="resposta_multipla" value="${letra.toUpperCase()}"
                                    class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
                                <span class="flex-1">
                                    <span class="font-semibold text-gray-700">${letra.toUpperCase()})</span>
                                    <span class="ml-2">${escapeHtml(alternativa)}</span>
                                </span>
                            </label>
                        `;
                    }).join('')}
                </div>
                <button onclick="submitAnswer()" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold">
                    <i class="fas fa-check"></i> Responder
                </button>
            `;
        }

        // Renderizar preencher lacuna
        function renderFillInBlank(questao) {
            const parts = questao.texto_com_lacuna.split('____');
            return `
                <div id="question-input" class="mb-4">
                    <div class="text-lg">
                        ${parts[0] || ''}
                        <input type="text" id="resposta_lacuna"
                            class="px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 mx-2"
                            placeholder="...">
                        ${parts[1] || ''}
                    </div>
                </div>
                <button onclick="submitAnswer()" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold">
                    <i class="fas fa-check"></i> Responder
                </button>
            `;
        }

        // Renderizar escrita
        function renderWriting(questao) {
            return `
                <div id="question-input" class="mb-4">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <p class="text-yellow-800 text-sm">
                            <i class="fas fa-lightbulb"></i> <strong>Dica:</strong> ${escapeHtml(questao.dicas || 'Escreva pelo menos 50 palavras')}
                        </p>
                    </div>
                    <textarea id="resposta_escrita" rows="8"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        placeholder="Escreva sua resposta aqui..."></textarea>
                    <p class="text-sm text-gray-500 mt-2">
                        <span id="word-count">0</span> palavras
                    </p>
                </div>
                <button onclick="submitAnswer()" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold">
                    <i class="fas fa-paper-plane"></i> Enviar para Avaliação
                </button>
            `;
        }

        // Contar palavras (para questões de escrita)
        document.addEventListener('input', function(e) {
            if (e.target.id === 'resposta_escrita') {
                const words = e.target.value.trim().split(/\s+/).filter(w => w.length > 0).length;
                const counter = document.getElementById('word-count');
                if (counter) {
                    counter.textContent = words;
                    counter.className = words >= 50 ? 'text-green-600 font-semibold' : 'text-gray-500';
                }
            }
        });

        // Submeter resposta
        async function submitAnswer() {
            const questao = questoes[currentQuestionIndex];
            const formData = new FormData();
            formData.append('tentativa_id', tentativaId);
            formData.append('questao_id', questao.id);
            formData.append('tipo_questao', questao.tipo_questao);

            // Coletar resposta baseado no tipo
            let resposta = null;
            if (questao.tipo_questao === 'multipla_escolha') {
                const selected = document.querySelector('input[name="resposta_multipla"]:checked');
                if (!selected) {
                    alert('Por favor, selecione uma alternativa');
                    return;
                }
                resposta = selected.value;
                formData.append('resposta_multipla', resposta);
            } else if (questao.tipo_questao === 'preencher_lacuna') {
                resposta = document.getElementById('resposta_lacuna').value.trim();
                if (!resposta) {
                    alert('Por favor, preencha a lacuna');
                    return;
                }
                formData.append('resposta_lacuna', resposta);
            } else if (questao.tipo_questao === 'escrita') {
                resposta = document.getElementById('resposta_escrita').value.trim();
                const words = resposta.split(/\s+/).filter(w => w.length > 0).length;
                if (words < 30) {
                    alert('Por favor, escreva pelo menos 30 palavras');
                    return;
                }
                formData.append('resposta_escrita', resposta);
            }

            // Desabilitar inputs e mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            document.getElementById('question-input').style.opacity = '0.5';
            document.getElementById('question-input').style.pointerEvents = 'none';

            try {
                const response = await fetch('/api/ingles_licoes.php?action=responder', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    respostasMap[questao.id] = {
                        answered: true,
                        correct: result.correta,
                        result: result
                    };

                    showFeedback(questao, result);
                    updateAllNavButtons();
                    updateProgress();
                    checkIfAllAnswered();
                } else {
                    alert('Erro: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    document.getElementById('question-input').style.opacity = '1';
                    document.getElementById('question-input').style.pointerEvents = 'auto';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao enviar resposta');
                btn.disabled = false;
                btn.innerHTML = originalText;
                document.getElementById('question-input').style.opacity = '1';
                document.getElementById('question-input').style.pointerEvents = 'auto';
            }
        }

        // Mostrar feedback
        function showFeedback(questao, result) {
            const feedbackArea = document.getElementById('feedback-area');
            const correta = result.correta;

            let html = `
                <div class="p-4 rounded-lg ${correta ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'}">
                    <h3 class="font-bold ${correta ? 'text-green-900' : 'text-red-900'} mb-2">
                        <i class="fas fa-${correta ? 'check-circle' : 'times-circle'}"></i>
                        ${correta ? 'Correto!' : 'Incorreto'}
                    </h3>
            `;

            if (questao.tipo_questao === 'escrita' && result.avaliacao) {
                const av = result.avaliacao;
                html += `
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Pontuação:</span>
                            <span class="text-2xl font-bold ${av.pontuacao >= 70 ? 'text-green-600' : 'text-yellow-600'}">${av.pontuacao}/100</span>
                        </div>
                        <div>
                            <p class="font-semibold mb-1">Feedback:</p>
                            <p class="text-gray-700">${escapeHtml(av.feedback)}</p>
                        </div>
                        ${av.pontos_fortes && av.pontos_fortes.length > 0 ? `
                            <div>
                                <p class="font-semibold text-green-700 mb-1"><i class="fas fa-check"></i> Pontos Fortes:</p>
                                <ul class="list-disc pl-5 text-gray-700">
                                    ${av.pontos_fortes.map(p => `<li>${escapeHtml(p)}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                        ${av.pontos_melhorar && av.pontos_melhorar.length > 0 ? `
                            <div>
                                <p class="font-semibold text-yellow-700 mb-1"><i class="fas fa-exclamation-triangle"></i> Pontos a Melhorar:</p>
                                <ul class="list-disc pl-5 text-gray-700">
                                    ${av.pontos_melhorar.map(p => `<li>${escapeHtml(p)}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                        ${av.sugestoes && av.sugestoes.length > 0 ? `
                            <div>
                                <p class="font-semibold text-blue-700 mb-1"><i class="fas fa-lightbulb"></i> Sugestões:</p>
                                <ul class="list-disc pl-5 text-gray-700">
                                    ${av.sugestoes.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                `;
            } else {
                if (!correta && result.resposta_correta) {
                    html += `<p class="text-sm mb-2">Resposta correta: <strong>${escapeHtml(result.resposta_correta)}</strong></p>`;
                }
                if (!correta && result.respostas_aceitas && result.respostas_aceitas.length > 0) {
                    html += `<p class="text-sm mb-2">Respostas aceitas: <strong>${result.respostas_aceitas.join(', ')}</strong></p>`;
                }
                if (result.explicacao) {
                    html += `
                        <div class="mt-2 pt-2 border-t ${correta ? 'border-green-200' : 'border-red-200'}">
                            <p class="text-sm font-semibold mb-1">Explicação:</p>
                            <p class="text-sm text-gray-700">${escapeHtml(result.explicacao)}</p>
                        </div>
                    `;
                }
            }

            html += `</div>`;
            feedbackArea.innerHTML = html;
        }

        // Mostrar feedback existente
        function showExistingFeedback(questao, resposta) {
            showFeedback(questao, resposta.result);
            document.getElementById('question-input').style.opacity = '0.5';
            document.getElementById('question-input').style.pointerEvents = 'none';
        }

        // Atualizar progresso
        function updateProgress() {
            const respondidas = Object.keys(respostasMap).length;
            document.getElementById('progress-text').textContent = `${respondidas}/${questoes.length}`;
            const percent = (respondidas / questoes.length) * 100;
            document.getElementById('progress-bar').style.width = percent + '%';
        }

        // Verificar se todas foram respondidas
        function checkIfAllAnswered() {
            if (Object.keys(respostasMap).length === questoes.length) {
                document.getElementById('btn-finalizar').classList.remove('hidden');
            }
        }

        // Navegação
        function previousQuestion() {
            if (currentQuestionIndex > 0) {
                renderQuestion(currentQuestionIndex - 1);
            }
        }

        function nextQuestion() {
            if (currentQuestionIndex < questoes.length - 1) {
                renderQuestion(currentQuestionIndex + 1);
            }
        }

        // Finalizar
        document.getElementById('btn-finalizar').addEventListener('click', function() {
            document.getElementById('total-respondidas').textContent = Object.keys(respostasMap).length;
            document.getElementById('modal-finalizar').classList.remove('hidden');
        });

        function fecharModalFinalizar() {
            document.getElementById('modal-finalizar').classList.add('hidden');
        }

        document.getElementById('btn-confirmar-finalizar').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';

            try {
                const formData = new FormData();
                formData.append('tentativa_id', tentativaId);

                const response = await fetch('/api/ingles_licoes.php?action=finalizar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = `/ingles/resultado_licao.php?tentativa_id=${tentativaId}`;
                } else {
                    alert('Erro ao finalizar: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = 'Finalizar';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao finalizar lição');
                btn.disabled = false;
                btn.innerHTML = 'Finalizar';
            }
        });

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
};

renderLayout('Realizar Lição', $content, true, true);
?>
