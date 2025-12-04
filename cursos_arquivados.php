<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$categorias = getCategorias();
$db = Database::getInstance();

// Buscar cursos arquivados com suas categorias
$cursos = $db->fetchAll("
    SELECT c.*, cat.nome as categoria_nome
    FROM cursos c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    INNER JOIN cursos_arquivados ca ON ca.curso_id = c.id AND ca.usuario_id = ?
    WHERE c.ativo = TRUE
    ORDER BY ca.data_arquivamento DESC, cat.nome, c.titulo
", [$_SESSION['user_id']]);

// Agrupar cursos por categoria e adicionar progresso
$cursosPorCategoria = [];
foreach ($cursos as $curso) {
    $categoriaNome = $curso['categoria_nome'] ?: 'Sem Categoria';
    if (!isset($cursosPorCategoria[$categoriaNome])) {
        $cursosPorCategoria[$categoriaNome] = [];
    }

    // Adicionar progresso do curso
    $curso['progresso'] = getProgressoCurso($curso['id'], $_SESSION['user_id']);
    $cursosPorCategoria[$categoriaNome][] = $curso;
}

$content = '
                <div class="max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-2">
                            <a href="/cursos.php" class="text-gray-600 hover:text-gray-900 transition-colors">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-3xl font-bold text-gray-900">
                                <i class="fas fa-archive mr-2 text-gray-600"></i>
                                Cursos Arquivados
                            </h1>
                        </div>
                        <p class="text-gray-600 ml-10">Gerencie seus cursos arquivados e desarquive quando quiser</p>
                    </div>

                ' . (empty($cursosPorCategoria) ? '
                <div class="text-center py-12">
                    <i class="fas fa-archive text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum curso arquivado</h3>
                    <p class="text-gray-500 mb-6">Você ainda não arquivou nenhum curso.</p>
                    <a href="/cursos.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-graduation-cap mr-2"></i>
                        Ver Todos os Cursos
                    </a>
                </div>' : '') . '

                ' . implode('', array_map(function($categoriaNome, $cursos) {
                    return '
                    <!-- Category Section -->
                    <div class="mb-12">
                        <div class="flex items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">' . htmlspecialchars($categoriaNome) . '</h2>
                            <span class="ml-3 bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-medium">
                                ' . count($cursos) . ' curso' . (count($cursos) > 1 ? 's' : '') . '
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ' . implode('', array_map(function($curso) {
                                return '
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow relative">
                                <!-- Archived Badge -->
                                <div class="absolute top-4 right-4 z-10">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-800 text-white shadow-lg">
                                        <i class="fas fa-archive mr-1"></i>
                                        Arquivado
                                    </span>
                                </div>

                                <!-- Course Image/Icon -->
                                <div class="h-48 ' . ($curso['imagem_capa'] ? 'bg-cover bg-center' : 'bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center') . '" ' . ($curso['imagem_capa'] ? 'style="background-image: url(\'' . htmlspecialchars($curso['imagem_capa']) . '\')"' : '') . '>
                                    ' . (!$curso['imagem_capa'] ? '<div class="text-center text-white">
                                        <i class="fas fa-graduation-cap text-4xl mb-2"></i>
                                        <p class="text-sm opacity-90">' . htmlspecialchars($curso['categoria_nome'] ?? 'Geral') . '</p>
                                    </div>' : '<div class="h-full w-full bg-gradient-to-t from-black/50 to-transparent flex items-end">
                                        <div class="p-4 text-white">
                                            <p class="text-sm font-medium">' . htmlspecialchars($curso['categoria_nome'] ?? 'Geral') . '</p>
                                        </div>
                                    </div>') . '
                                </div>

                                <!-- Course Info -->
                                <div class="p-6">
                                    <div class="mb-3">
                                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                                            <span>Progresso</span>
                                            <span>' . $curso['progresso']['progresso_percentual'] . '%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-gray-600 h-1.5 rounded-full transition-all duration-300" style="width: ' . $curso['progresso']['progresso_percentual'] . '%"></div>
                                        </div>
                                    </div>

                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <a href="/curso.php?id=' . $curso['id'] . '" class="hover:text-gray-600 transition-colors">
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

                                    <div class="flex gap-2">
                                        <a href="/curso.php?id=' . $curso['id'] . '"
                                           class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                                            <i class="fas fa-arrow-right mr-2"></i>
                                            ' . ($curso['progresso']['progresso_percentual'] > 0 ? 'Continuar' : 'Iniciar') . '
                                        </a>
                                        <button onclick="desarquivarCurso(' . $curso['id'] . ')"
                                                class="inline-flex items-center justify-center px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-200 transition-colors"
                                                title="Desarquivar curso">
                                            <i class="fas fa-undo mr-1"></i>
                                            Restaurar
                                        </button>
                                    </div>
                                </div>
                            </div>';
                            }, $cursos)) . '
                        </div>
                    </div>';
                }, array_keys($cursosPorCategoria), array_values($cursosPorCategoria))) . '
                </div>

                <script>
                async function desarquivarCurso(cursoId) {
                    if (!confirm("Deseja desarquivar este curso? Ele voltará para a listagem principal.")) {
                        return;
                    }

                    try {
                        const response = await fetch("/api/arquivar_curso.php?curso_id=" + cursoId, {
                            method: "DELETE"
                        });

                        const data = await response.json();

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert("Erro: " + data.error);
                        }
                    } catch (error) {
                        alert("Erro ao desarquivar curso: " + error.message);
                    }
                }
                </script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Cursos Arquivados', $content, true, true);
?>
