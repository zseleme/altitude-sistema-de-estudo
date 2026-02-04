<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$query = $_GET['q'] ?? '';
$resultados = [];

if (!empty($query)) {
    $resultados = pesquisarConteudo($query, $_SESSION['user_id']);
}

$content = '
                <div class="max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Resultados da Pesquisa</h1>
                        <p class="text-gray-600">Encontramos ' . ($resultados['total'] ?? 0) . ' resultado(s) para "' . htmlspecialchars($query) . '"</p>
                    </div>
                    
                    
                    ' . (empty($query) ? '
                    <div class="text-center py-12">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">Digite algo para pesquisar</h3>
                        <p class="text-gray-500">Use a barra de pesquisa acima para encontrar cursos e aulas.</p>
                    </div>' : '') . '
                    
                    ' . (empty($resultados) && !empty($query) ? '
                    <div class="text-center py-12">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum resultado encontrado</h3>
                        <p class="text-gray-500">Tente usar termos diferentes ou verifique a ortografia.</p>
                    </div>' : '') . '
                    
                    ' . (!empty($resultados) ? '
                    <!-- Cursos -->
                    ' . (!empty($resultados['cursos']) ? '
                    <div class="mb-12">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-graduation-cap mr-2 text-blue-600"></i>
                            Cursos (' . count($resultados['cursos']) . ')
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ' . implode('', array_map(function($curso) {
                                return '
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                                <!-- Course Image/Icon -->
                                <div class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <i class="fas fa-graduation-cap text-4xl mb-2"></i>
                                        <p class="text-sm opacity-90">' . htmlspecialchars($curso['categoria_nome'] ?? 'Geral') . '</p>
                                    </div>
                                </div>
                                
                                <!-- Course Info -->
                                <div class="p-6">
                                    <div class="mb-3">
                                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                                            <span>Progresso</span>
                                            <span>' . $curso['progresso']['progresso_percentual'] . '%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width: ' . $curso['progresso']['progresso_percentual'] . '%"></div>
                                        </div>
                                    </div>
                                    
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <a href="/curso.php?id=' . $curso['id'] . '" class="hover:text-blue-600 transition-colors">
                                            ' . htmlspecialchars($curso['titulo']) . '
                                        </a>
                                    </h3>
                                    
                                    ' . ($curso['descricao'] ? '
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                        ' . htmlspecialchars(substr($curso['descricao'], 0, 100)) . (strlen($curso['descricao']) > 100 ? '...' : '') . '
                                    </p>' : '') . '
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-play-circle mr-1"></i>
                                            <span>' . $curso['progresso']['aulas_concluidas'] . ' / ' . $curso['progresso']['total_aulas'] . ' aulas</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-clock mr-1"></i>
                                            <span>' . $curso['progresso']['tempo_estudado'] . ' min</span>
                                        </div>
                                    </div>
                                    
                                    <a href="/curso.php?id=' . $curso['id'] . '" 
                                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-arrow-right mr-2"></i>
                                        ' . ($curso['progresso']['progresso_percentual'] > 0 ? 'Continuar Curso' : 'Iniciar Curso') . '
                                    </a>
                                </div>
                            </div>';
                            }, $resultados['cursos'])) . '
                        </div>
                    </div>' : '') . '
                    
                    <!-- Aulas -->
                    ' . (!empty($resultados['aulas']) ? '
                    <div class="mb-12">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-play-circle mr-2 text-green-600"></i>
                            Aulas (' . count($resultados['aulas']) . ')
                        </h2>
                        <div class="space-y-4">
                            ' . implode('', array_map(function($aula) {
                                return '
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full mr-3">
                                                ' . htmlspecialchars($aula['categoria_nome'] ?? 'Geral') . '
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                ' . htmlspecialchars($aula['curso_titulo']) . '
                                            </span>
                                        </div>
                                        
                                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                                            <a href="/aula.php?id=' . $aula['id'] . '" class="hover:text-blue-600 transition-colors">
                                                ' . htmlspecialchars($aula['titulo']) . '
                                            </a>
                                        </h3>
                                        
                                        ' . ($aula['descricao'] ? '
                                        <p class="text-gray-600 text-sm mb-3">
                                            ' . htmlspecialchars(substr($aula['descricao'], 0, 150)) . (strlen($aula['descricao']) > 150 ? '...' : '') . '
                                        </p>' : '') . '
                                        
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            <span>' . ($aula['duracao_minutos'] ?? 30) . ' minutos</span>
                                        </div>
                                    </div>
                                    
                                    <a href="/aula.php?id=' . $aula['id'] . '" 
                                       class="ml-4 inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="fas fa-play mr-2"></i>
                                        Assistir
                                    </a>
                                </div>
                            </div>';
                            }, $resultados['aulas'])) . '
                        </div>
                    </div>' : '') . '
                    ' : '') . '
                </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Pesquisa', $content, true, true);
?>
