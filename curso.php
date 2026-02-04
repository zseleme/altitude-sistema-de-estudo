<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$cursoId = (int)($_GET['id'] ?? 0);
if (!$cursoId) {
    header('Location: /home.php');
    exit;
}

$curso = getCursoById($cursoId);
if (!$curso) {
    header('Location: /home.php');
    exit;
}

$aulas = getAulasByCurso($cursoId);
$categorias = getCategorias();
$db = Database::getInstance();

// Buscar progresso do curso
$progressoCurso = getProgressoCurso($cursoId, $_SESSION['user_id']);

// Buscar status de conclusão de todas as aulas
$aulasConcluidas = [];
if (!empty($aulas)) {
    $aulaIds = array_column($aulas, 'id');
    if (!empty($aulaIds)) {
        try {
            $placeholders = str_repeat('?,', count($aulaIds) - 1) . '?';
            $progressos = $db->fetchAll(
                "SELECT aula_id, concluida FROM progresso_aulas WHERE usuario_id = ? AND aula_id IN ($placeholders)",
                array_merge([$_SESSION['user_id']], $aulaIds)
            );
            
            foreach ($progressos as $progresso) {
                $aulasConcluidas[$progresso['aula_id']] = $progresso['concluida'];
            }
        } catch (Exception $e) {
            // Se houver erro, continuar sem indicadores de conclusão
            error_log("Erro ao buscar progresso das aulas: " . $e->getMessage());
        }
    }
}

$content = '
                <!-- Breadcrumb -->
                <nav class="flex mb-6" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="/home.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                <i class="fas fa-home mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">' . htmlspecialchars($curso['titulo']) . '</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Course Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-4">' . htmlspecialchars($curso['titulo']) . '</h1>
                            ' . ($curso['descricao'] ? '<p class="text-gray-600 text-lg mb-4">' . htmlspecialchars($curso['descricao']) . '</p>' : '') . '
                            
                            <div class="flex items-center space-x-6 text-sm text-gray-500">
                                <div class="flex items-center">
                                    <i class="fas fa-play-circle mr-2 text-blue-600"></i>
                                    <span>' . count($aulas) . ' aulas</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock mr-2 text-green-600"></i>
                                    <span>Duração: ' . array_sum(array_map(function($aula) { return $aula['duracao_minutos'] ?? 30; }, $aulas)) . ' min</span>
                            </div>
                                <div class="flex items-center">
                                    <i class="fas fa-tag mr-2 text-purple-600"></i>
                                    <span>' . htmlspecialchars($curso['categoria_nome'] ?? 'Sem categoria') . '</span>
                    </div>
                            </div>
                        </div>
                        
                        <div class="ml-8">
                            <div class="w-32 h-32 bg-gradient-to-br from-blue-600 to-purple-700 rounded-xl flex items-center justify-center">
                                <i class="fas fa-graduation-cap text-4xl text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Lessons List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h2 class="text-xl font-bold text-gray-900">
                                    <i class="fas fa-list mr-2 text-blue-600"></i>
                                    Aulas do Curso
                                </h2>
                            </div>
                            
                            <div class="divide-y divide-gray-200">
                                ' . (empty($aulas) ? '
                                <div class="p-8 text-center">
                                    <i class="fas fa-video text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma aula disponível</h3>
                                    <p class="text-gray-500">Este curso ainda não possui aulas cadastradas.</p>
                                </div>' : '') . '
                                
                                ' . implode('', array_map(function($aula, $index) use ($aulasConcluidas) {
                                    $isCompleted = isset($aulasConcluidas[$aula['id']]) && $aulasConcluidas[$aula['id']];
                                    return '
                                <div class="p-6 hover:bg-gray-50 transition-colors ' . ($isCompleted ? 'bg-green-50 border-l-4 border-green-500' : '') . '">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="relative">
                                                <div class="w-10 h-10 ' . ($isCompleted ? 'bg-green-100' : 'bg-blue-100') . ' rounded-full flex items-center justify-center">
                                                    <span class="text-sm font-semibold ' . ($isCompleted ? 'text-green-600' : 'text-blue-600') . '">' . ($index + 1) . '</span>
                                                </div>
                                                ' . ($isCompleted ? '
                                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-check text-white text-xs"></i>
                                                </div>
                                                ' : '') . '
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 mb-1 flex items-center">
                                                    ' . htmlspecialchars($aula['titulo']) . '
                                                    ' . ($isCompleted ? '<span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Concluída</span>' : '') . '
                                                </h3>
                                                ' . ($aula['descricao'] ? '<p class="text-sm text-gray-600">' . htmlspecialchars(substr($aula['descricao'], 0, 100)) . (strlen($aula['descricao']) > 100 ? '...' : '') . '</p>' : '') . '
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm text-gray-500">' . ($aula['duracao_minutos'] ?? 30) . ' min</span>
                                            <a href="/aula.php?id=' . $aula['id'] . '" 
                                               class="inline-flex items-center px-4 py-2 ' . ($isCompleted ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700') . ' text-white text-sm font-medium rounded-lg transition-colors">
                                                <i class="fas fa-' . ($isCompleted ? 'redo' : 'play') . ' mr-2"></i>
                                                ' . ($isCompleted ? 'Revisar' : 'Assistir') . '
                                            </a>
                                        </div>
                                    </div>
                                </div>';
                                }, $aulas, array_keys($aulas))) . '
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course Info Sidebar -->
                    <div class="space-y-6">
                        <!-- Progress Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-chart-line mr-2 text-green-600"></i>
                                Seu Progresso
                            </h3>
                            
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progresso Geral</span>
                                    <span>' . $progressoCurso['progresso_percentual'] . '%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: ' . $progressoCurso['progresso_percentual'] . '%"></div>
                                </div>
                            </div>
                            
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span>Aulas Concluídas</span>
                                    <span>' . $progressoCurso['aulas_concluidas'] . ' / ' . $progressoCurso['total_aulas'] . '</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tempo Estudado</span>
                                    <span>' . $progressoCurso['tempo_estudado'] . ' min</span>
                                </div>
                                        </div>
                                    </div>
                        
                        <!-- Course Stats -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                                Informações do Curso
                            </h3>
                            
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Categoria</span>
                                    <span class="font-medium text-gray-900">' . htmlspecialchars($curso['categoria_nome'] ?? 'Sem categoria') . '</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total de Aulas</span>
                                    <span class="font-medium text-gray-900">' . count($aulas) . '</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Duração Estimada</span>
                                    <span class="font-medium text-gray-900">' . count($aulas) * 30 . ' min</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Status</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Ativo
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-cog mr-2 text-gray-600"></i>
                                Ações
                            </h3>
                            
                            <div class="space-y-3">
                                <a href="/home.php" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Voltar ao Dashboard
                                </a>
                                
                                ' . (isAdmin() ? '
                                <a href="/admin/cursos.php?edit=' . $curso['id'] . '" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-edit mr-2"></i>
                                    Editar Curso
                                </a>' : '') . '
                            </div>
                        </div>
                    </div>
                </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout(htmlspecialchars($curso['titulo']), $content, true, true);
?>