<?php
// Teste de deploy FTP - 2025-12-06
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
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Bem-vindo, ' . htmlspecialchars($_SESSION['user_name']) . '!</h1>
                        <p class="text-gray-600">Escolha uma área para começar sua jornada de aprendizado</p>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold">' . $userStats['aulas_concluidas'] . '</div>
                                    <div class="text-sm opacity-90">Aulas Concluídas</div>
                                </div>
                                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check-circle text-lg"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold">' . $userStats['cursos_com_progresso'] . '</div>
                                    <div class="text-sm opacity-90">Cursos Ativos</div>
                                </div>
                                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-graduation-cap text-lg"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold">' . floor($userStats['tempo_estudado'] / 60) . 'h ' . ($userStats['tempo_estudado'] % 60) . 'm</div>
                                    <div class="text-sm opacity-90">Tempo Estudado</div>
                                </div>
                                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-lg"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold">' . $userStats['streak_dias'] . '</div>
                                    <div class="text-sm opacity-90">Dias Ativos</div>
                                </div>
                                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-fire text-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Navigation Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Estatísticas Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-md transition-shadow">
                            <div class="text-center">
                                <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Minhas Estatísticas</h3>
                                <p class="text-gray-600 mb-6">Acompanhe seu progresso detalhado, tempo estudado e conquistas.</p>
                                <a href="/estatisticas.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Ver Estatísticas
                                </a>
                            </div>
                        </div>
                        
                        <!-- Cursos Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 hover:shadow-md transition-shadow">
                            <div class="text-center">
                                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-graduation-cap text-2xl text-green-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Meus Cursos</h3>
                                <p class="text-gray-600 mb-6">Explore e continue seus cursos organizados por categoria.</p>
                                <a href="/cursos.php" class="inline-flex items-center px-6 py-3 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Ver Cursos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Dashboard', $content, true, true);
?>