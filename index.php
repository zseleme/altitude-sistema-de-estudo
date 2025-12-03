<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redirecionar para o home
if (isLoggedIn()) {
    header('Location: /home.php');
    exit;
}

// Buscar estatísticas gerais
$stats = getEstatisticasGerais();

$content = '
    <div class="min-h-screen bg-gray-900 relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -inset-[10px] opacity-50">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
                <div class="absolute top-1/3 right-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
                <div class="absolute bottom-1/4 left-1/3 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-4000"></div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="relative bg-gray-900/80 backdrop-blur-xl border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <div class="flex items-center">
                            <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/50">
                                <i class="fas fa-mountain text-xl text-white"></i>
                            </div>
                            <div class="ml-3">
                                <h1 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">Altitude</h1>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/login.php" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Entrar
                        </a>
                        <a href="/register.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2.5 rounded-lg font-medium hover:from-blue-500 hover:to-purple-500 transition-all shadow-lg shadow-blue-500/30">
                            <i class="fas fa-user-plus mr-2"></i>
                            Cadastrar
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 mb-8">
                    <span class="relative flex h-2 w-2 mr-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    <span class="text-sm text-blue-400 font-medium">Plataforma em constante evolução</span>
                </div>

                <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                    <span class="text-white">Aprenda de forma</span>
                    <br>
                    <span class="bg-gradient-to-r from-blue-400 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                        organizada e eficiente
                    </span>
                </h1>

                <p class="text-xl text-gray-400 mb-12 max-w-3xl mx-auto leading-relaxed">
                    Organize seus estudos por categorias e cursos. Acesse aulas em vídeo,
                    faça anotações, acompanhe seu progresso e alcance novos patamares no aprendizado.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="/register.php" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 transition-all transform hover:scale-105 shadow-2xl shadow-blue-500/50">
                        <span class="absolute inset-0 w-full h-full rounded-xl bg-gradient-to-r from-blue-400 to-purple-500 blur opacity-30 group-hover:opacity-50 transition-opacity"></span>
                        <span class="relative flex items-center">
                            <i class="fas fa-rocket mr-2"></i>
                            Começar Agora
                        </span>
                    </a>
                    <a href="/login.php" class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white rounded-xl border-2 border-gray-700 hover:border-gray-600 hover:bg-gray-800 transition-all">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Já tenho conta
                    </a>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Recursos poderosos para seu aprendizado</h2>
                <p class="text-xl text-gray-400">Tudo o que você precisa em uma única plataforma</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-blue-500/50 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/0 via-blue-600/0 to-blue-600/0 group-hover:from-blue-600/10 group-hover:via-blue-600/5 group-hover:to-blue-600/10 rounded-2xl transition-all duration-300"></div>
                    <div class="relative">
                        <div class="h-16 w-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/50 group-hover:scale-110 transition-transform">
                            <i class="fas fa-video text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Aulas em Vídeo HD</h3>
                        <p class="text-gray-400">Acesse conteúdo de alta qualidade, suporte para YouTube, Vimeo e mais. Controle de velocidade e legendas.</p>
                    </div>
                </div>

                <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-purple-500/50 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-600/0 via-purple-600/0 to-purple-600/0 group-hover:from-purple-600/10 group-hover:via-purple-600/5 group-hover:to-purple-600/10 rounded-2xl transition-all duration-300"></div>
                    <div class="relative">
                        <div class="h-16 w-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-purple-500/50 group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-line text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Progresso Detalhado</h3>
                        <p class="text-gray-400">Monitore cada passo com relatórios, gráficos e estatísticas. Veja seu crescimento em tempo real.</p>
                    </div>
                </div>

                <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-pink-500/50 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-pink-600/0 via-pink-600/0 to-pink-600/0 group-hover:from-pink-600/10 group-hover:via-pink-600/5 group-hover:to-pink-600/10 rounded-2xl transition-all duration-300"></div>
                    <div class="relative">
                        <div class="h-16 w-16 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-pink-500/50 group-hover:scale-110 transition-transform">
                            <i class="fas fa-robot text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">IA Integrada</h3>
                        <p class="text-gray-400">Revisão de textos em inglês com inteligência artificial. Suporte a múltiplos provedores de IA.</p>
                    </div>
                </div>

                <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-green-500/50 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-600/0 via-green-600/0 to-green-600/0 group-hover:from-green-600/10 group-hover:via-green-600/5 group-hover:to-green-600/10 rounded-2xl transition-all duration-300"></div>
                    <div class="relative">
                        <div class="h-16 w-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-green-500/50 group-hover:scale-110 transition-transform">
                            <i class="fas fa-sticky-note text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Anotações Inteligentes</h3>
                        <p class="text-gray-400">Faça anotações por aula, organize seu conhecimento e revise quando quiser. Tudo sincronizado.</p>
                    </div>
                </div>

                <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-yellow-500/50 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-yellow-600/0 via-yellow-600/0 to-yellow-600/0 group-hover:from-yellow-600/10 group-hover:via-yellow-600/5 group-hover:to-yellow-600/10 rounded-2xl transition-all duration-300"></div>
                    <div class="relative">
                        <div class="h-16 w-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-yellow-500/50 group-hover:scale-110 transition-transform">
                            <i class="fas fa-certificate text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Certificados</h3>
                        <p class="text-gray-400">Receba certificados profissionais ao concluir cursos. PDF gerado automaticamente para download.</p>
                    </div>
                </div>

                <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-indigo-500/50 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/0 via-indigo-600/0 to-indigo-600/0 group-hover:from-indigo-600/10 group-hover:via-indigo-600/5 group-hover:to-indigo-600/10 rounded-2xl transition-all duration-300"></div>
                    <div class="relative">
                        <div class="h-16 w-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/50 group-hover:scale-110 transition-transform">
                            <i class="fab fa-youtube text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Importação do YouTube</h3>
                        <p class="text-gray-400">Importe playlists completas do YouTube automaticamente. Organize seus cursos favoritos.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">A plataforma em números</h2>
                <p class="text-xl text-gray-400">Crescimento constante com mais conteúdo todos os dias</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 text-center border border-gray-700 hover:border-blue-500/50 transition-all group">
                    <div class="h-14 w-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/50 group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">' . number_format($stats['total_usuarios']) . '</div>
                    <div class="text-sm text-gray-400">Usuários Ativos</div>
                </div>

                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 text-center border border-gray-700 hover:border-green-500/50 transition-all group">
                    <div class="h-14 w-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-500/50 group-hover:scale-110 transition-transform">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">' . number_format($stats['total_cursos']) . '</div>
                    <div class="text-sm text-gray-400">Cursos Disponíveis</div>
                </div>

                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 text-center border border-gray-700 hover:border-purple-500/50 transition-all group">
                    <div class="h-14 w-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-purple-500/50 group-hover:scale-110 transition-transform">
                        <i class="fas fa-video text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">' . number_format($stats['total_aulas']) . '</div>
                    <div class="text-sm text-gray-400">Aulas Publicadas</div>
                </div>

                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 text-center border border-gray-700 hover:border-yellow-500/50 transition-all group">
                    <div class="h-14 w-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-yellow-500/50 group-hover:scale-110 transition-transform">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">' . number_format($stats['total_aulas_concluidas']) . '</div>
                    <div class="text-sm text-gray-400">Aulas Concluídas</div>
                </div>

                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 text-center border border-gray-700 hover:border-pink-500/50 transition-all group">
                    <div class="h-14 w-14 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-pink-500/50 group-hover:scale-110 transition-transform">
                        <i class="fas fa-tags text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">' . number_format($stats['total_categorias']) . '</div>
                    <div class="text-sm text-gray-400">Categorias</div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="relative bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20 backdrop-blur-sm rounded-3xl p-12 text-center border border-gray-700 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 opacity-10"></div>
                <div class="relative">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">Pronto para começar?</h2>
                    <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">
                        Junte-se a milhares de estudantes que já estão transformando suas vidas através da educação.
                    </p>
                    <a href="/register.php" class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 transition-all transform hover:scale-105 shadow-2xl shadow-blue-500/50">
                        <i class="fas fa-rocket mr-3"></i>
                        Começar Gratuitamente
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="relative border-t border-gray-800 bg-gray-900/50 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-blue-500/50">
                            <i class="fas fa-mountain text-lg text-white"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">Altitude</span>
                            <p class="text-xs text-gray-500">Eleve seu conhecimento</p>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="text-gray-400">© 2024 Altitude. Todos os direitos reservados.</p>
                        <p class="text-sm text-gray-600 mt-1">Desenvolvido com 💜 para estudantes</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <style>
        @keyframes blob {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Início', $content, false, false);
?>
