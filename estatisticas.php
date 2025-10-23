<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Buscar estatísticas do usuário
$userStats = getEstatisticasUsuario($_SESSION['user_id']);

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
                                    <div class="text-3xl font-bold">' . gmdate("H:i", $userStats['tempo_estudado'] * 60) . '</div>
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
                        <!-- Progress Chart Placeholder -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
                                Progresso por Categoria
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Desenvolvimento Web</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">75%</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Programação</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: 45%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">45%</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Design</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: 90%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">90%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-history mr-2 text-green-600"></i>
                                Atividade Recente
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Aula concluída</p>
                                        <p class="text-xs text-gray-500">Introdução ao HTML - há 2 horas</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-play text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Aula iniciada</p>
                                        <p class="text-xs text-gray-500">CSS Avançado - há 1 dia</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-trophy text-yellow-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Curso concluído</p>
                                        <p class="text-xs text-gray-500">Fundamentos de JavaScript - há 3 dias</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Minhas Estatísticas', $content, true, true);
?>
