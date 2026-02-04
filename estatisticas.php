<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = Database::getInstance();
$usuarioId = $_SESSION['user_id'];

// Buscar estatísticas do usuário
$userStats = getEstatisticasUsuario($usuarioId);

// Buscar progresso por categoria
$progressoPorCategoria = $db->fetchAll("
    SELECT
        cat.nome as categoria,
        COUNT(DISTINCT a.id) as total_aulas,
        COUNT(DISTINCT CASE WHEN pa.concluida = TRUE THEN a.id END) as aulas_concluidas
    FROM categorias cat
    JOIN cursos c ON c.categoria_id = cat.id AND c.ativo = TRUE
    JOIN aulas a ON a.curso_id = c.id AND a.ativo = TRUE
    LEFT JOIN progresso_aulas pa ON pa.aula_id = a.id AND pa.usuario_id = ?
    GROUP BY cat.id, cat.nome
    HAVING total_aulas > 0
    ORDER BY cat.nome
", [$usuarioId]);

// Buscar atividade recente
$atividadeRecente = $db->fetchAll("
    SELECT
        a.titulo as aula_titulo,
        c.titulo as curso_titulo,
        pa.data_conclusao,
        pa.concluida
    FROM progresso_aulas pa
    JOIN aulas a ON pa.aula_id = a.id
    JOIN cursos c ON a.curso_id = c.id
    WHERE pa.usuario_id = ?
    ORDER BY pa.data_conclusao DESC
    LIMIT 10
", [$usuarioId]);

$content = '
                <div class="max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Minhas Estatísticas</h1>
                        <p class="text-gray-600">Acompanhe seu progresso e desempenho na plataforma</p>
                    </div>
                    
                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <!-- Aulas Concluídas -->
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-3xl font-bold">' . $userStats['aulas_concluidas'] . '</div>
                                    <div class="text-sm opacity-90">Aulas Concluídas</div>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check-circle text-2xl"></i>
                                </div>
                            </div>
                            <div class="text-sm opacity-75">
                                de ' . $userStats['total_aulas'] . ' aulas disponíveis
                            </div>
                        </div>
                        
                        <!-- Cursos Ativos -->
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-3xl font-bold">' . $userStats['cursos_com_progresso'] . '</div>
                                    <div class="text-sm opacity-90">Cursos Ativos</div>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-graduation-cap text-2xl"></i>
                                </div>
                            </div>
                            <div class="text-sm opacity-75">
                                de ' . $userStats['cursos_inscritos'] . ' cursos disponíveis
                            </div>
                        </div>
                        
                        <!-- Tempo Estudado -->
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-3xl font-bold">' . floor($userStats['tempo_estudado'] / 60) . 'h ' . ($userStats['tempo_estudado'] % 60) . 'm</div>
                                    <div class="text-sm opacity-90">Tempo Estudado</div>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-2xl"></i>
                                </div>
                            </div>
                            <div class="text-sm opacity-75">
                                ' . round($userStats['tempo_estudado'] / 60, 1) . ' horas totais
                            </div>
                        </div>
                        
                        <!-- Progresso Geral -->
                        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-3xl font-bold">' . round(($userStats['aulas_concluidas'] / max($userStats['total_aulas'], 1)) * 100, 1) . '%</div>
                                    <div class="text-sm opacity-90">Progresso Geral</div>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-line text-2xl"></i>
                                </div>
                            </div>
                            <div class="text-sm opacity-75">
                                ' . $userStats['aulas_concluidas'] . ' de ' . $userStats['total_aulas'] . ' aulas
                            </div>
                        </div>
                        
                        <!-- Streak de Dias -->
                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-3xl font-bold">' . $userStats['streak_dias'] . '</div>
                                    <div class="text-sm opacity-90">Dias Ativos</div>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-fire text-2xl"></i>
                                </div>
                            </div>
                            <div class="text-sm opacity-75">
                                nos últimos 30 dias
                            </div>
                        </div>
                        
                        <!-- Cursos Disponíveis -->
                        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-3xl font-bold">' . $userStats['cursos_inscritos'] . '</div>
                                    <div class="text-sm opacity-90">Cursos Disponíveis</div>
                                </div>
                                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-book text-2xl"></i>
                                </div>
                            </div>
                            <div class="text-sm opacity-75">
                                para explorar
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Statistics -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Progress Chart -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
                                Progresso por Categoria
                            </h3>
                            ' . (empty($progressoPorCategoria) ? '
                            <div class="text-center py-8">
                                <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">Nenhum progresso registrado ainda</p>
                            </div>' : '
                            <div class="space-y-4">
                                ' . implode('', array_map(function($cat, $index) {
                                    $progresso = $cat['total_aulas'] > 0 ? round(($cat['aulas_concluidas'] / $cat['total_aulas']) * 100) : 0;
                                    $cores = ['blue', 'green', 'purple', 'red', 'yellow', 'indigo'];
                                    $cor = $cores[$index % count($cores)];

                                    return '
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">' . htmlspecialchars($cat['categoria']) . '</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-' . $cor . '-600 h-2 rounded-full" style="width: ' . $progresso . '%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">' . $progresso . '%</span>
                                    </div>
                                </div>';
                                }, $progressoPorCategoria, array_keys($progressoPorCategoria))) . '
                            </div>') . '
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-history mr-2 text-green-600"></i>
                                Atividade Recente
                            </h3>
                            ' . (empty($atividadeRecente) ? '
                            <div class="text-center py-8">
                                <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                            </div>' : '
                            <div class="space-y-3">
                                ' . implode('', array_map(function($atividade) {
                                    $agora = new DateTime();
                                    $dataConclusao = new DateTime($atividade['data_conclusao']);
                                    $diff = $agora->diff($dataConclusao);

                                    if ($diff->days == 0) {
                                        if ($diff->h > 0) {
                                            $tempo = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                                        } else {
                                            $tempo = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
                                        }
                                    } elseif ($diff->days == 1) {
                                        $tempo = '1 dia';
                                    } else {
                                        $tempo = $diff->days . ' dias';
                                    }

                                    return '
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">' . htmlspecialchars($atividade['aula_titulo']) . '</p>
                                        <p class="text-xs text-gray-500">' . htmlspecialchars($atividade['curso_titulo']) . ' - há ' . $tempo . '</p>
                                    </div>
                                </div>';
                                }, $atividadeRecente)) . '
                            </div>') . '
                        </div>
                    </div>
                </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Minhas Estatísticas', $content, true, true);
?>
