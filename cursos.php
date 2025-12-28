<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$categorias = getCategorias();
$db = Database::getInstance();

// Contar cursos arquivados
$cursosArquivados = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM cursos_arquivados ca
    JOIN cursos c ON ca.curso_id = c.id
    WHERE ca.usuario_id = ? AND c.ativo = TRUE
", [$_SESSION['user_id']])['total'] ?? 0;

// Buscar cursos favoritos (com flag e ordenados por data)
$cursosFavoritos = $db->fetchAll("
    SELECT c.*, cat.nome as categoria_nome, 1 as is_favorito
    FROM cursos c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    INNER JOIN cursos_favoritos cf ON cf.curso_id = c.id AND cf.usuario_id = ?
    LEFT JOIN cursos_arquivados ca ON ca.curso_id = c.id AND ca.usuario_id = ?
    WHERE c.ativo = TRUE AND ca.curso_id IS NULL
    ORDER BY cf.data_favoritado DESC
", [$_SESSION['user_id'], $_SESSION['user_id']]);

// Buscar todos os cursos com suas categorias (excluindo arquivados) e flag de favorito
$cursos = $db->fetchAll("
    SELECT c.*, cat.nome as categoria_nome,
           CASE WHEN cf.curso_id IS NOT NULL THEN 1 ELSE 0 END as is_favorito
    FROM cursos c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN cursos_arquivados ca ON ca.curso_id = c.id AND ca.usuario_id = ?
    LEFT JOIN cursos_favoritos cf ON cf.curso_id = c.id AND cf.usuario_id = ?
    WHERE c.ativo = TRUE AND ca.curso_id IS NULL
    ORDER BY cat.nome, c.titulo
", [$_SESSION['user_id'], $_SESSION['user_id']]);

// Processar favoritos com progresso
$cursosFavoritosComProgresso = [];
foreach ($cursosFavoritos as $curso) {
    $curso['progresso'] = getProgressoCurso($curso['id'], $_SESSION['user_id']);
    $cursosFavoritosComProgresso[] = $curso;
}

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

// Função helper para renderizar card de curso
function renderCourseCard($curso) {
    $isFavorito = isset($curso['is_favorito']) && $curso['is_favorito'] == 1;

    return '
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
        <!-- Course Image/Icon -->
        <div class="h-48 ' . ($curso['imagem_capa'] ? 'bg-cover bg-center' : 'bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center') . '" ' . ($curso['imagem_capa'] ? 'style="background-image: url(\'' . htmlspecialchars($curso['imagem_capa']) . '\')"' : '') . '>
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

            <div class="flex gap-2">
                <a href="/curso.php?id=' . $curso['id'] . '"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-arrow-right mr-2"></i>
                    ' . ($curso['progresso']['progresso_percentual'] > 0 ? 'Continuar' : 'Iniciar') . '
                </a>

                <!-- Favorite Toggle Button -->
                <button onclick="toggleFavorito(' . $curso['id'] . ', ' . ($isFavorito ? 'true' : 'false') . ')"
                        id="fav-btn-' . $curso['id'] . '"
                        class="inline-flex items-center justify-center px-3 py-2 ' . ($isFavorito ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200') . ' text-sm font-medium rounded-lg transition-colors"
                        title="' . ($isFavorito ? 'Remover dos favoritos' : 'Adicionar aos favoritos') . '">
                    <i class="' . ($isFavorito ? 'fas' : 'far') . ' fa-heart" id="fav-icon-' . $curso['id'] . '"></i>
                </button>

                <!-- Archive Button -->
                <button onclick="arquivarCurso(' . $curso['id'] . ')"
                        class="inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors"
                        title="Arquivar curso">
                    <i class="fas fa-archive"></i>
                </button>
            </div>
        </div>
    </div>';
}

$content = '
                <div class="max-w-7xl mx-auto">
                    <!-- Header -->
                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Meus Cursos</h1>
                            <p class="text-gray-600">Explore e continue seus cursos de aprendizado</p>
                        </div>
                        ' . ($cursosArquivados > 0 ? '
                        <a href="/cursos_arquivados.php" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-archive mr-2"></i>
                            Arquivados (' . $cursosArquivados . ')
                        </a>' : '') . '
                    </div>

                    <!-- Seção de Favoritos -->
                    ' . (!empty($cursosFavoritosComProgresso) ? '
                    <div class="mb-12">
                        <div class="flex items-center mb-6">
                            <i class="fas fa-heart text-red-500 text-2xl mr-3"></i>
                            <h2 class="text-2xl font-bold text-gray-900">Meus Favoritos</h2>
                            <span class="ml-3 bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium">
                                ' . count($cursosFavoritosComProgresso) . ' curso' . (count($cursosFavoritosComProgresso) > 1 ? 's' : '') . '
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ' . implode('', array_map(function($curso) {
                                return renderCourseCard($curso);
                            }, $cursosFavoritosComProgresso)) . '
                        </div>
                    </div>' : '') . '

                ' . (empty($cursosPorCategoria) ? '
                <div class="text-center py-12">
                    <i class="fas fa-graduation-cap text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhum curso disponível</h3>
                    <p class="text-gray-500 mb-6">Ainda não há cursos cadastrados na plataforma.</p>
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
                                return renderCourseCard($curso);
                            }, $cursos)) . '
                        </div>
                    </div>';
                }, array_keys($cursosPorCategoria), array_values($cursosPorCategoria))) . '
                </div>

                <script>
                async function arquivarCurso(cursoId) {
                    if (!confirm("Deseja arquivar este curso? Ele será movido para a seção de cursos arquivados.")) {
                        return;
                    }

                    try {
                        const response = await fetch("/api/arquivar_curso.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({ curso_id: cursoId })
                        });

                        const data = await response.json();

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert("Erro: " + data.error);
                        }
                    } catch (error) {
                        alert("Erro ao arquivar curso: " + error.message);
                    }
                }

                async function toggleFavorito(cursoId, isFavorito) {
                    const btn = document.getElementById("fav-btn-" + cursoId);
                    const icon = document.getElementById("fav-icon-" + cursoId);

                    if (!btn || !icon) return;

                    // Desabilitar botão durante request
                    btn.disabled = true;

                    try {
                        if (isFavorito) {
                            // Remover dos favoritos
                            const response = await fetch("/api/favoritar_curso.php?curso_id=" + cursoId, {
                                method: "DELETE"
                            });

                            const data = await response.json();

                            if (data.success) {
                                // Atualizar UI
                                icon.className = "far fa-heart";
                                btn.className = "inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-600 hover:bg-gray-200 text-sm font-medium rounded-lg transition-colors";
                                btn.title = "Adicionar aos favoritos";
                                btn.onclick = function() { toggleFavorito(cursoId, false); };

                                showToast("Removido dos favoritos", "success");

                                // Recarregar se estava na seção de favoritos
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                throw new Error(data.error || "Erro ao remover favorito");
                            }
                        } else {
                            // Adicionar aos favoritos
                            const response = await fetch("/api/favoritar_curso.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({ curso_id: cursoId })
                            });

                            const data = await response.json();

                            if (data.success) {
                                // Atualizar UI
                                icon.className = "fas fa-heart";
                                btn.className = "inline-flex items-center justify-center px-3 py-2 bg-red-100 text-red-600 hover:bg-red-200 text-sm font-medium rounded-lg transition-colors";
                                btn.title = "Remover dos favoritos";
                                btn.onclick = function() { toggleFavorito(cursoId, true); };

                                showToast("Adicionado aos favoritos!", "success");
                            } else {
                                throw new Error(data.error || "Erro ao adicionar favorito");
                            }
                        }
                    } catch (error) {
                        alert("Erro ao atualizar favoritos: " + error.message);
                    } finally {
                        btn.disabled = false;
                    }
                }

                function showToast(message, type) {
                    const toast = document.createElement("div");
                    toast.className = "fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-opacity " +
                        (type === "success" ? "bg-green-500" : "bg-red-500");
                    toast.textContent = message;
                    toast.style.opacity = "1";

                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.style.opacity = "0";
                        setTimeout(() => toast.remove(), 300);
                    }, 2000);
                }
                </script>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Meus Cursos', $content, true, true);
?>
