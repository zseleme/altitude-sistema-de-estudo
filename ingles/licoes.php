<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_helper.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Verificar se IA está configurada
$aiEnabled = AIHelper::isConfigured();

require_once __DIR__ . '/../includes/layout.php';

$content = function() use ($db, $userId, $aiEnabled) {
    ?>

    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500">
            <a href="/home.php" class="hover:text-blue-600">Home</a>
            <span class="mx-2">›</span>
            <a href="#" class="hover:text-blue-600">Inglês</a>
            <span class="mx-2">›</span>
            <span class="text-gray-900 font-medium">Lições de Inglês</span>
        </nav>
    </div>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            <i class="fas fa-graduation-cap text-blue-600"></i> Lições de Inglês
        </h1>
        <p class="text-gray-600">
            Gere lições personalizadas com IA sobre qualquer tema. Cada lição inclui questões de múltipla escolha,
            preenchimento de lacunas e escrita.
        </p>
    </div>

    <?php if (!$aiEnabled): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        A IA não está configurada. Configure em
                        <a href="/admin/configuracoes_ia.php" class="font-medium underline">Administração > Configurações de IA</a>.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mensagens -->
    <div id="message-container"></div>

    <!-- Formulário de Geração de Lição -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-magic text-purple-600"></i> Gerar Nova Lição
        </h2>

        <form id="form-gerar-licao" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Tema -->
                <div class="md:col-span-2">
                    <label for="tema" class="block text-sm font-medium text-gray-700 mb-2">
                        Tema da Lição *
                    </label>
                    <input
                        type="text"
                        id="tema"
                        name="tema"
                        required
                        placeholder="Ex: Daily Routines, Food & Drinks, Travel & Tourism..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        <?php echo !$aiEnabled ? 'disabled' : ''; ?>
                    >
                </div>

                <!-- Nível -->
                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-2">
                        Nível
                    </label>
                    <select
                        id="nivel"
                        name="nivel"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        <?php echo !$aiEnabled ? 'disabled' : ''; ?>
                    >
                        <option value="basico">Básico (A1-A2)</option>
                        <option value="intermediario" selected>Intermediário (B1-B2)</option>
                        <option value="avancado">Avançado (C1-C2)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button
                    type="submit"
                    id="btn-gerar"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 disabled:bg-gray-400 disabled:cursor-not-allowed"
                    <?php echo !$aiEnabled ? 'disabled' : ''; ?>
                >
                    <i class="fas fa-magic mr-2"></i>
                    <span id="btn-text">Gerar Lição</span>
                </button>
                <p class="text-sm text-gray-500">
                    <i class="fas fa-info-circle"></i> Cada lição contém 9 questões variadas
                </p>
            </div>
        </form>

        <!-- Loading State -->
        <div id="loading-licao" class="hidden mt-4 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-4"></div>
                <div>
                    <p class="text-blue-900 font-medium">Gerando lição com IA...</p>
                    <p class="text-blue-700 text-sm">Isso pode levar de 5 a 15 segundos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Lições -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
            <i class="fas fa-list text-blue-600"></i> Minhas Lições
        </h2>
    </div>

    <!-- Loading -->
    <div id="loading-lista" class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Carregando lições...</p>
    </div>

    <!-- Grid de Lições -->
    <div id="licoes-grid" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Cards serão inseridos aqui via JavaScript -->
    </div>

    <!-- Estado Vazio -->
    <div id="empty-state" class="hidden text-center py-12">
        <i class="fas fa-book-open text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-600 text-lg mb-2">Nenhuma lição criada ainda</p>
        <p class="text-gray-500">Gere sua primeira lição usando o formulário acima!</p>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="modal-delete" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-trash text-red-600"></i> Confirmar Exclusão
            </h3>
            <p class="text-gray-700 mb-6">
                Tem certeza que deseja excluir esta lição? Esta ação não pode ser desfeita.
            </p>
            <div class="flex justify-end space-x-3">
                <button onclick="fecharModalDelete()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">
                    Cancelar
                </button>
                <button id="btn-confirmar-delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    Excluir
                </button>
            </div>
        </div>
    </div>

    <script>
        let licaoParaExcluir = null;

        // Carregar lições ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            carregarLicoes();
        });

        // Gerar nova lição
        document.getElementById('form-gerar-licao').addEventListener('submit', async function(e) {
            e.preventDefault();

            const tema = document.getElementById('tema').value.trim();
            const nivel = document.getElementById('nivel').value;

            if (!tema) {
                mostrarMensagem('Por favor, informe o tema da lição', 'error');
                return;
            }

            const btn = document.getElementById('btn-gerar');
            const btnText = document.getElementById('btn-text');
            const loading = document.getElementById('loading-licao');

            btn.disabled = true;
            btnText.textContent = 'Gerando...';
            loading.classList.remove('hidden');

            try {
                const formData = new FormData();
                formData.append('tema', tema);
                formData.append('nivel', nivel);

                const response = await fetch('/api/ingles_licoes.php?action=gerar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    mostrarMensagem('Lição gerada com sucesso!', 'success');
                    document.getElementById('form-gerar-licao').reset();
                    carregarLicoes();
                } else {
                    mostrarMensagem(result.message || 'Erro ao gerar lição', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao comunicar com o servidor', 'error');
            } finally {
                btn.disabled = false;
                btnText.textContent = 'Gerar Lição';
                loading.classList.add('hidden');
            }
        });

        // Carregar lições
        async function carregarLicoes() {
            const loadingLista = document.getElementById('loading-lista');
            const licoesGrid = document.getElementById('licoes-grid');
            const emptyState = document.getElementById('empty-state');

            loadingLista.classList.remove('hidden');
            licoesGrid.classList.add('hidden');
            emptyState.classList.add('hidden');

            try {
                const response = await fetch('/api/ingles_licoes.php?action=listar');
                const result = await response.json();

                if (result.success) {
                    const licoes = result.licoes;

                    if (licoes.length === 0) {
                        emptyState.classList.remove('hidden');
                    } else {
                        renderLicoes(licoes);
                        licoesGrid.classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar lições:', error);
                mostrarMensagem('Erro ao carregar lições', 'error');
            } finally {
                loadingLista.classList.add('hidden');
            }
        }

        // Renderizar lições
        function renderLicoes(licoes) {
            const grid = document.getElementById('licoes-grid');
            grid.innerHTML = '';

            licoes.forEach(licao => {
                const nivelClass = {
                    'basico': 'bg-green-100 text-green-800',
                    'intermediario': 'bg-yellow-100 text-yellow-800',
                    'avancado': 'bg-red-100 text-red-800'
                }[licao.nivel] || 'bg-gray-100 text-gray-800';

                const nivelText = {
                    'basico': 'Básico',
                    'intermediario': 'Intermediário',
                    'avancado': 'Avançado'
                }[licao.nivel] || licao.nivel;

                const card = document.createElement('div');
                card.className = 'bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden';
                card.innerHTML = `
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-bold text-gray-900 flex-1">
                                ${escapeHtml(licao.titulo)}
                            </h3>
                            <button onclick="abrirModalDelete(${licao.id})" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <p class="text-gray-600 text-sm mb-3">${escapeHtml(licao.descricao || '')}</p>
                        <div class="flex items-center space-x-2 mb-4">
                            <span class="text-xs px-2 py-1 rounded-full ${nivelClass} font-medium">
                                ${nivelText}
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-book"></i> ${licao.numero_questoes} questões
                            </span>
                        </div>
                        <div class="border-t pt-3 mt-3 flex items-center justify-between text-sm text-gray-600">
                            <div>
                                <i class="fas fa-redo"></i> ${licao.tentativas_count || 0} tentativas
                            </div>
                            ${licao.melhor_nota ? `
                                <div class="text-green-600 font-medium">
                                    <i class="fas fa-star"></i> ${Math.round(licao.melhor_nota)}%
                                </div>
                            ` : ''}
                        </div>
                        <div class="mt-4 flex space-x-2">
                            <a href="/ingles/realizar_licao.php?id=${licao.id}"
                               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                <i class="fas fa-play"></i> Iniciar
                            </a>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // Mostrar mensagem
        function mostrarMensagem(texto, tipo) {
            const container = document.getElementById('message-container');
            const classesTipo = tipo === 'success'
                ? 'bg-green-50 border-green-400 text-green-700'
                : 'bg-red-50 border-red-400 text-red-700';

            const icone = tipo === 'success' ? 'check-circle' : 'exclamation-circle';

            const mensagem = document.createElement('div');
            mensagem.className = `border-l-4 p-4 mb-4 ${classesTipo}`;
            mensagem.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${icone} mr-2"></i>
                    <span>${escapeHtml(texto)}</span>
                </div>
            `;
            container.innerHTML = '';
            container.appendChild(mensagem);

            setTimeout(() => {
                mensagem.remove();
            }, 5000);
        }

        // Modal de exclusão
        function abrirModalDelete(id) {
            licaoParaExcluir = id;
            document.getElementById('modal-delete').classList.remove('hidden');
        }

        function fecharModalDelete() {
            licaoParaExcluir = null;
            document.getElementById('modal-delete').classList.add('hidden');
        }

        document.getElementById('btn-confirmar-delete').addEventListener('click', async function() {
            if (!licaoParaExcluir) return;

            try {
                const formData = new FormData();
                formData.append('id', licaoParaExcluir);

                const response = await fetch('/api/ingles_licoes.php?action=excluir', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    mostrarMensagem('Lição excluída com sucesso', 'success');
                    carregarLicoes();
                } else {
                    mostrarMensagem(result.message || 'Erro ao excluir lição', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao comunicar com o servidor', 'error');
            } finally {
                fecharModalDelete();
            }
        });

        // Escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }
    </script>

    <?php
};

renderLayout('Lições de Inglês', $content, true, true);
?>
