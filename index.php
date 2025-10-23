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
    <div class="min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-800">
        <!-- Navigation -->
        <nav class="bg-white/10 backdrop-blur-md border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 bg-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-mountain text-xl text-blue-600"></i>
                            </div>
                        </div>
                        <div class="ml-3">
                            <h1 class="text-xl font-bold text-white">Altitude</h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        ' . (isLoggedIn() ? '
                        <a href="/home.php" class="text-white hover:text-blue-200 transition-colors">
                            <i class="fas fa-home mr-2"></i>
                            Minha Área
                        </a>
                        <a href="/logout.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-blue-50 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Sair
                        </a>
                        ' : '
                        <a href="/login.php" class="text-white hover:text-blue-200 transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Entrar
                        </a>
                        <a href="/register.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-blue-50 transition-colors">
                            <i class="fas fa-user-plus mr-2"></i>
                            Cadastrar
                        </a>
                        ') . '
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    Aprenda de forma
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">
                        organizada
                    </span>
                        </h1>
                <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto">
                            Organize seus estudos por categorias e cursos. Acesse aulas em vídeo, 
                    faça anotações e acompanhe seu progresso de forma eficiente.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="/login.php" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-blue-50 transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Começar Agora
                    </a>
                    <a href="/register.php" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white hover:text-blue-600 transition-all transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>
                        Criar Conta
                    </a>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Por que escolher nossa plataforma?</h2>
                <p class="text-xl text-blue-100">Recursos pensados para maximizar seu aprendizado</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all">
                    <div class="text-center">
                        <div class="h-16 w-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-video text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Aulas em Vídeo</h3>
                        <p class="text-blue-100">Acesse conteúdo de alta qualidade em vídeo, com suporte a diferentes formatos e velocidades.</p>
                    </div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all">
                    <div class="text-center">
                        <div class="h-16 w-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-line text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Acompanhe Progresso</h3>
                        <p class="text-blue-100">Monitore seu desenvolvimento com relatórios detalhados e estatísticas de aprendizado.</p>
                    </div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all">
                    <div class="text-center">
                        <div class="h-16 w-16 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-sticky-note text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Anotações</h3>
                        <p class="text-blue-100">Faça anotações personalizadas durante as aulas e organize seu conhecimento.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">Números que Impressionam</h2>
                <p class="text-xl text-blue-100">Nossa plataforma cresce a cada dia com mais conteúdo e usuários</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 text-center border border-white/20 hover:bg-white/20 transition-all">
                    <div class="h-12 w-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-users text-white text-lg"></i>
                    </div>
                    <div class="text-2xl font-bold text-white mb-1">' . number_format($stats['total_usuarios']) . '</div>
                    <div class="text-sm text-blue-100">Usuários</div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 text-center border border-white/20 hover:bg-white/20 transition-all">
                    <div class="h-12 w-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-graduation-cap text-white text-lg"></i>
                    </div>
                    <div class="text-2xl font-bold text-white mb-1">' . number_format($stats['total_cursos']) . '</div>
                    <div class="text-sm text-blue-100">Cursos</div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 text-center border border-white/20 hover:bg-white/20 transition-all">
                    <div class="h-12 w-12 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-video text-white text-lg"></i>
                    </div>
                    <div class="text-2xl font-bold text-white mb-1">' . number_format($stats['total_aulas']) . '</div>
                    <div class="text-sm text-blue-100">Aulas</div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 text-center border border-white/20 hover:bg-white/20 transition-all">
                    <div class="h-12 w-12 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check-circle text-white text-lg"></i>
                    </div>
                    <div class="text-2xl font-bold text-white mb-1">' . number_format($stats['total_aulas_concluidas']) . '</div>
                    <div class="text-sm text-blue-100">Aulas Concluídas</div>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 text-center border border-white/20 hover:bg-white/20 transition-all">
                    <div class="h-12 w-12 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-tags text-white text-lg"></i>
                    </div>
                    <div class="text-2xl font-bold text-white mb-1">' . number_format($stats['total_categorias']) . '</div>
                    <div class="text-sm text-blue-100">Categorias</div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="bg-white/10 backdrop-blur-md rounded-3xl p-12 text-center border border-white/20">
                ' . (isLoggedIn() ? '
                <h2 class="text-4xl font-bold text-white mb-4">Bem-vindo de volta!</h2>
                <p class="text-xl text-blue-100 mb-8">Continue sua jornada de aprendizado e alcance novos patamares.</p>
                <a href="/home.php" class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:from-yellow-500 hover:to-orange-600 transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-home mr-2"></i>
                    Ir para Minha Área
                </a>
                ' : '
                <h2 class="text-4xl font-bold text-white mb-4">Pronto para começar?</h2>
                <p class="text-xl text-blue-100 mb-8">Junte-se a milhares de estudantes que já estão aprendendo conosco.</p>
                <a href="/register.php" class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:from-yellow-500 hover:to-orange-600 transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-rocket mr-2"></i>
                    Começar Gratuitamente
                </a>
                ') . '
        </div>
    </div>

        <!-- Footer -->
        <footer class="bg-black/20 backdrop-blur-md border-t border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <div class="flex items-center justify-center mb-4">
                        <div class="h-8 w-8 bg-white rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-mountain text-lg text-blue-600"></i>
                        </div>
                        <span class="text-xl font-bold text-white">Altitude</span>
                    </div>
                    <p class="text-blue-100">© 2024 Altitude. Todos os direitos reservados.</p>
                </div>
            </div>
        </footer>
    </div>';

require_once __DIR__ . '/includes/layout.php';
renderLayout('Início', $content, false, false);
?>