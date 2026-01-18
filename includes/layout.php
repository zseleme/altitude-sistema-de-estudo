<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/csrf_helper.php';
require_once __DIR__ . '/security_headers.php';

// Apply security headers
SecurityHeaders::apply();

// Função para renderizar o layout base
function renderLayout($title, $content, $showSidebar = true, $isLoggedIn = false) {
    $userName = $isLoggedIn ? $_SESSION['user_name'] ?? 'Usuário' : '';
    $userInitial = $isLoggedIn ? strtoupper(substr($userName, 0, 1)) : '';
    $isAdmin = $isLoggedIn && isAdmin();
    
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Altitude</title>
    ' . CSRFHelper::getTokenMeta() . '
    <link href="favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#3b82f6",
                        "secondary": "#64748b", 
                        "accent": "#f59e0b",
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">';

    if ($isLoggedIn) {
        echo '    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 z-50 h-16">
        <div class="flex items-center justify-between h-full px-6">
            <!-- Logo Altitude -->
            <div class="flex items-center space-x-3">
                <a href="/index.php" class="flex items-center space-x-2">
                    <i class="fas fa-mountain text-2xl text-blue-600"></i>
                    <span class="text-xl font-bold text-gray-900">Altitude</span>
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="flex-1 max-w-2xl mx-8">
                <form method="GET" action="/pesquisa.php" class="relative" id="search-form">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" 
                           id="search-input"
                           name="q"
                           autocomplete="off"
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Pesquise por cursos, módulos ou aulas"
                           value="' . htmlspecialchars($_GET['q'] ?? '') . '">
                    
                    <!-- Autocomplete Dropdown -->
                    <div id="autocomplete-results" class="hidden absolute z-50 mt-1 w-full bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto">
                        <!-- Results will be inserted here -->
                    </div>
                </form>
            </div>
            
            <!-- User Controls -->
            <div class="flex items-center space-x-4">
                <!-- Pomodoro Timer Button -->
                <button id="pomodoro-toggle" class="p-2 text-gray-400 hover:text-gray-600 transition-colors relative" title="Timer Pomodoro">
                    <i class="fas fa-clock text-xl"></i>
                    <span id="pomodoro-mini-timer" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">25</span>
                </button>
                
                <button class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-bell text-xl"></i>
                </button>
                <div class="relative">
                    <button class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-white font-semibold hover:bg-gray-700 transition-colors">
                        ' . $userInitial . '
                    </button>
                    <!-- User Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden" id="userDropdown">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-900">' . htmlspecialchars($userName) . '</p>
                            <p class="text-sm text-gray-500">' . htmlspecialchars($_SESSION['user_email'] ?? '') . '</p>
                        </div>
                        <a href="/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-globe mr-2"></i>Home Pública
                        </a>
                        <a href="/home.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-home mr-2"></i>Minha Área
                        </a>
                        <a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="flex pt-16">';

        if ($showSidebar) {
            echo '        <!-- Left Sidebar -->
        <aside class="w-64 bg-gray-800 text-white fixed left-0 top-16 bottom-0 overflow-y-auto z-40 transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full">
            <!-- Logo na Sidebar
            <div class="p-4 border-b border-gray-700">
                <a href="/dashboard.php" class="flex items-center space-x-2">
                    <i class="fas fa-mountain text-xl text-blue-400"></i>
                    <span class="text-lg font-bold text-white">Altitude</span>
                </a>
            </div>-->

            <nav class="p-4 flex flex-col min-h-full">
                <div class="flex-grow">
                <ul class="space-y-2">
                    <li>
                        <a href="/home.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-700 rounded-lg transition-colors">
                            <i class="fas fa-home w-5 mr-3"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="/cursos.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-700 rounded-lg transition-colors">
                            <i class="fas fa-graduation-cap w-5 mr-3"></i>
                            <span>Meus Cursos</span>
                        </a>
                    </li>
                    <li>
                        <a href="/certificados.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-700 rounded-lg transition-colors">
                            <i class="fas fa-certificate w-5 mr-3"></i>
                            <span>Certificados</span>
                        </a>
                    </li>
                    <li>
                        <a href="/simulados.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-700 rounded-lg transition-colors">
                            <i class="fas fa-file-alt w-5 mr-3"></i>
                            <span>Simulados</span>
                        </a>
                    </li>

                </ul>

                <!-- English Section -->
                <div class="mt-8 pt-4 border-t border-gray-700">
                    <h3 class="px-4 text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">🇺🇸 Inglês</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="/ingles/licoes.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-graduation-cap w-5 mr-3"></i>
                                <span>Lições de Inglês</span>
                            </a>
                        </li>
                        <li>
                            <a href="/ingles/anotacoes.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-sticky-note w-5 mr-3"></i>
                                <span>Anotações</span>
                            </a>
                        </li>
                        <li>
                            <a href="/ingles/diario.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-book-open w-5 mr-3"></i>
                                <span>Diário</span>
                            </a>
                        </li>
                    </ul>
                </div>';

            if ($isAdmin) {
                echo '
                <div class="mt-8 pt-4 border-t border-gray-700">
                    <button onclick="toggleAdminDropdown()" class="w-full flex items-center justify-between px-4 py-2 text-sm font-semibold text-gray-400 uppercase tracking-wider hover:bg-gray-700 rounded-lg transition-colors">
                        <span>Administração</span>
                        <i id="admin-dropdown-icon" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                    </button>
                    <ul id="admin-dropdown-menu" class="space-y-2 mt-2 hidden">
                        <li>
                            <a href="/admin/categorias.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-folder w-5 mr-3"></i>
                                <span>Categorias</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin/cursos.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-book w-5 mr-3"></i>
                                <span>Cursos</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin/aulas.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-video w-5 mr-3"></i>
                                <span>Aulas</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin/usuarios.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-users w-5 mr-3"></i>
                                <span>Usuários</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin/database.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-database w-5 mr-3"></i>
                                <span>Base de Dados</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin/configuracoes_banco.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-cog w-5 mr-3"></i>
                                <span>Configurações do Banco</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin/configuracoes_ia.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-robot w-5 mr-3"></i>
                                <span>Configurações de IA</span>
                            </a>
                        </li>
                        <li>
                            <a href="/admin_simulados.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-file-alt w-5 mr-3"></i>
                                <span>Simulados</span>
                            </a>
                        </li>
                    </ul>
                </div>';
            }

            echo '                </div>'; // Fecha flex-grow

            // Adicionar versão no rodapé do menu
            $versionInfo = getAppVersion();
            echo '
                <!-- Versão do Sistema -->
                <div class="mt-auto pt-4 border-t border-gray-700">
                    <div class="px-4 py-3">
                        <div class="text-xs text-gray-500 space-y-1">
                            <div class="flex items-center justify-between">
                                <span>Versão</span>
                                <span class="text-gray-400 font-mono">' . htmlspecialchars($versionInfo['version']) . '</span>
                            </div>';

            if ($versionInfo['commit'] !== 'local') {
                echo '                            <div class="flex items-center justify-between">
                                <span>Commit</span>
                                <span class="text-gray-400 font-mono">' . htmlspecialchars($versionInfo['commit']) . '</span>
                            </div>';
            }

            if ($versionInfo['environment']) {
                $envColor = $versionInfo['environment'] === 'Produção' ? 'text-green-400' : 'text-yellow-400';
                echo '                            <div class="flex items-center justify-between">
                                <span>Ambiente</span>
                                <span class="' . $envColor . ' font-medium">' . htmlspecialchars($versionInfo['environment']) . '</span>
                            </div>';
            }

            echo '                        </div>
                    </div>
                </div>';

            echo '            </nav>
        </aside>

        <!-- Pomodoro Timer Modal -->
        <div id="pomodoro-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-clock mr-2 text-blue-600"></i>
                            Timer Pomodoro
                        </h3>
                        <button id="pomodoro-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Timer Display -->
                    <div class="text-center mb-6">
                        <div id="pomodoro-timer" class="text-4xl font-mono text-gray-900 mb-2">25:00</div>
                        <div id="pomodoro-status" class="text-lg text-gray-600 mb-4">Foco</div>
                        
                        <!-- Progress Circle -->
                        <div class="relative w-24 h-24 mx-auto mb-4">
                            <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path id="pomodoro-progress" class="text-blue-500" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="100, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span id="pomodoro-round" class="text-sm text-gray-600 font-medium">1/4</span>
                            </div>
                        </div>
                        
                        <!-- Controls -->
                        <div class="flex justify-center space-x-3">
                            <button id="pomodoro-start" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-play mr-2"></i>Iniciar
                            </button>
                            <button id="pomodoro-pause" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors" style="display: none;">
                                <i class="fas fa-pause mr-2"></i>Pausar
                            </button>
                            <button id="pomodoro-reset" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-stop mr-2"></i>Reset
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick Settings -->
                    <div class="space-y-2">
                        <button id="pomodoro-focus" class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 rounded-lg transition-colors border border-gray-200">
                            <i class="fas fa-brain mr-3 text-blue-600"></i>Foco (25min)
                        </button>
                        <button id="pomodoro-short-break" class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-green-50 rounded-lg transition-colors border border-gray-200">
                            <i class="fas fa-coffee mr-3 text-green-600"></i>Pausa Curta (5min)
                        </button>
                        <button id="pomodoro-long-break" class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 rounded-lg transition-colors border border-gray-200">
                            <i class="fas fa-bed mr-3 text-purple-600"></i>Pausa Longa (15min)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Minimized Pomodoro Bar -->
        <div id="pomodoro-minimized" class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white p-3 z-40 hidden">
            <div class="flex items-center justify-between max-w-7xl mx-auto">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-clock text-blue-400"></i>
                        <span id="pomodoro-mini-status" class="text-sm font-medium">Foco</span>
                    </div>
                    <div id="pomodoro-mini-timer-display" class="text-lg font-mono">25:00</div>
                    <div class="flex items-center space-x-2">
                        <button id="pomodoro-mini-start" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors">
                            <i class="fas fa-play"></i>
                        </button>
                        <button id="pomodoro-mini-pause" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm transition-colors" style="display: none;">
                            <i class="fas fa-pause"></i>
                        </button>
                        <button id="pomodoro-mini-reset" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="pomodoro-mini-expand" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button id="pomodoro-mini-close" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">';
        } else {
            echo '        <!-- Main Content (Full Width) -->
        <main class="flex-1 p-8">';
        }
        
        echo '            <div class="max-w-7xl mx-auto">';

        // Handle both string and callable content
        if (is_callable($content)) {
            ob_start();
            $content(); // Call the function to render output
            echo ob_get_clean(); // Capture and echo the output
        } else {
            echo $content; // Echo string content directly
        }

        echo '            </div>
        </main>
    </div>

    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-800 text-white rounded-lg shadow-lg">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile Backdrop -->
    <div id="mobile-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden transition-opacity duration-300"></div>

    <script>
        // Admin dropdown toggle with localStorage persistence
        function toggleAdminDropdown() {
            const menu = document.getElementById("admin-dropdown-menu");
            const icon = document.getElementById("admin-dropdown-icon");

            if (menu && icon) {
                const isHidden = menu.classList.toggle("hidden");
                icon.classList.toggle("rotate-180");

                // Salvar o estado no localStorage
                localStorage.setItem("adminDropdownOpen", isHidden ? "false" : "true");
            }
        }

        // Restaurar estado do dropdown ao carregar a página
        document.addEventListener("DOMContentLoaded", function() {
            const menu = document.getElementById("admin-dropdown-menu");
            const icon = document.getElementById("admin-dropdown-icon");

            if (menu && icon) {
                const isOpen = localStorage.getItem("adminDropdownOpen") === "true";

                if (isOpen) {
                    menu.classList.remove("hidden");
                    icon.classList.add("rotate-180");
                }
            }
        });

        // User dropdown toggle
        document.addEventListener("DOMContentLoaded", function() {
            const userButton = document.querySelector(".w-10.h-10.bg-gray-800");
            const userDropdown = document.getElementById("userDropdown");

            if (userButton && userDropdown) {
                userButton.addEventListener("click", function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle("hidden");
                });

                document.addEventListener("click", function() {
                    userDropdown.classList.add("hidden");
                });
            }
        });

        // Mobile menu toggle
        document.getElementById("mobile-menu-btn").addEventListener("click", function() {
            const sidebar = document.querySelector("aside");
            const backdrop = document.getElementById("mobile-backdrop");

            if (sidebar) {
                const isOpen = sidebar.classList.toggle("-translate-x-full");

                // Toggle backdrop
                if (backdrop) {
                    if (isOpen) {
                        backdrop.classList.add("hidden");
                    } else {
                        backdrop.classList.remove("hidden");
                    }
                }
            }
        });

        // Close mobile menu when clicking backdrop
        const backdrop = document.getElementById("mobile-backdrop");
        if (backdrop) {
            backdrop.addEventListener("click", function() {
                const sidebar = document.querySelector("aside");
                if (sidebar) {
                    sidebar.classList.add("-translate-x-full");
                    backdrop.classList.add("hidden");
                }
            });
        }

        // Responsive sidebar
        function handleResize() {
            const sidebar = document.querySelector("aside");
            const main = document.querySelector("main");
            const backdrop = document.getElementById("mobile-backdrop");

            if (window.innerWidth < 768) {
                // Mobile: esconder sidebar e remover margem do main
                if (sidebar) sidebar.classList.add("-translate-x-full");
                if (main) main.classList.remove("ml-64");
                if (backdrop) backdrop.classList.add("hidden");
            } else {
                // Desktop: mostrar sidebar e adicionar margem do main
                if (sidebar) sidebar.classList.remove("-translate-x-full");
                if (main) main.classList.add("ml-64");
                if (backdrop) backdrop.classList.add("hidden");
            }
        }

        window.addEventListener("resize", handleResize);
        
        // Search functionality with autocomplete
        const searchInput = document.getElementById("search-input");
        const autocompleteResults = document.getElementById("autocomplete-results");
        let autocompleteTimeout = null;
        let selectedIndex = -1;
        
        if (searchInput && autocompleteResults) {
            // Autocomplete on input
            searchInput.addEventListener("input", function(e) {
                const query = this.value.trim();
                
                // Limpar timeout anterior
                if (autocompleteTimeout) {
                    clearTimeout(autocompleteTimeout);
                }
                
                // Esconder se vazio
                if (query.length < 2) {
                    autocompleteResults.classList.add("hidden");
                    return;
                }
                
                // Debounce - aguardar 300ms após parar de digitar
                autocompleteTimeout = setTimeout(function() {
                    fetch("/api/autocomplete.php?q=" + encodeURIComponent(query))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.results.length > 0) {
                                displayAutocompleteResults(data.results);
                            } else {
                                autocompleteResults.classList.add("hidden");
                            }
                        })
                        .catch(error => {
                            console.error("Erro no autocomplete:", error);
                            autocompleteResults.classList.add("hidden");
                        });
                }, 300);
            });
            
            // Navegação com teclado
            searchInput.addEventListener("keydown", function(e) {
                const items = autocompleteResults.querySelectorAll(".autocomplete-item");
                
                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelectedItem(items);
                } else if (e.key === "ArrowUp") {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelectedItem(items);
                } else if (e.key === "Enter") {
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        e.preventDefault();
                        items[selectedIndex].click();
                    } else {
                        // Submit normal do formulário
                        e.preventDefault();
                        const form = this.closest("form");
                        if (form) {
                            form.submit();
                        }
                    }
                } else if (e.key === "Escape") {
                    autocompleteResults.classList.add("hidden");
                    selectedIndex = -1;
                }
            });
            
            // Fechar ao clicar fora
            document.addEventListener("click", function(e) {
                if (!searchInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
                    autocompleteResults.classList.add("hidden");
                    selectedIndex = -1;
                }
            });
            
            // Auto-focus on search page
            if (window.location.pathname === "/pesquisa.php") {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        function displayAutocompleteResults(results) {
            selectedIndex = -1;
            let html = "";
            
            results.forEach((item, index) => {
                const iconColor = item.tipo === "curso" ? "text-blue-600" : "text-green-600";
                const bgColor = item.tipo === "curso" ? "bg-blue-50" : "bg-green-50";
                
                html += `
                    <a href="${item.url}" 
                       class="autocomplete-item flex items-center px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
                       data-index="${index}">
                        <div class="w-10 h-10 ${bgColor} rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas ${item.icon} ${iconColor}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">${escapeHtml(item.titulo)}</div>
                            <div class="text-xs text-gray-500 truncate">${escapeHtml(item.subtitulo)}</div>
                        </div>
                        <div class="ml-2">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${bgColor} ${iconColor}">
                                ${item.tipo === "curso" ? "Curso" : "Aula"}
                            </span>
                        </div>
                    </a>
                `;
            });
            
            autocompleteResults.innerHTML = html;
            autocompleteResults.classList.remove("hidden");
        }
        
        function updateSelectedItem(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add("bg-blue-50");
                    item.scrollIntoView({ block: "nearest" });
                } else {
                    item.classList.remove("bg-blue-50");
                }
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement("div");
            div.textContent = text;
            return div.innerHTML;
        }
        handleResize(); // Initial call

        // Pomodoro Timer
        class PomodoroTimer {
            constructor() {
                this.timeLeft = 25 * 60; // 25 minutes in seconds
                this.totalTime = 25 * 60;
                this.isRunning = false;
                this.isPaused = false;
                this.currentMode = "focus"; // focus, shortBreak, longBreak
                this.round = 1;
                this.maxRounds = 4;
                this.interval = null;
                this.isMinimized = false;
                this.startTime = null;
                this.pausedTime = 0;
                
                this.initializeElements();
                this.bindEvents();
                this.loadState();
                this.updateDisplay();
            }
            
            initializeElements() {
                // Modal elements
                this.modal = document.getElementById("pomodoro-modal");
                this.timerDisplay = document.getElementById("pomodoro-timer");
                this.statusDisplay = document.getElementById("pomodoro-status");
                this.progressCircle = document.getElementById("pomodoro-progress");
                this.roundDisplay = document.getElementById("pomodoro-round");
                this.startBtn = document.getElementById("pomodoro-start");
                this.pauseBtn = document.getElementById("pomodoro-pause");
                this.resetBtn = document.getElementById("pomodoro-reset");
                this.focusBtn = document.getElementById("pomodoro-focus");
                this.shortBreakBtn = document.getElementById("pomodoro-short-break");
                this.longBreakBtn = document.getElementById("pomodoro-long-break");
                this.closeBtn = document.getElementById("pomodoro-close");
                
                // Header button
                this.toggleBtn = document.getElementById("pomodoro-toggle");
                this.miniTimer = document.getElementById("pomodoro-mini-timer");
                
                // Minimized bar elements
                this.minimizedBar = document.getElementById("pomodoro-minimized");
                this.miniStatus = document.getElementById("pomodoro-mini-status");
                this.miniTimerDisplay = document.getElementById("pomodoro-mini-timer-display");
                this.miniStartBtn = document.getElementById("pomodoro-mini-start");
                this.miniPauseBtn = document.getElementById("pomodoro-mini-pause");
                this.miniResetBtn = document.getElementById("pomodoro-mini-reset");
                this.miniExpandBtn = document.getElementById("pomodoro-mini-expand");
                this.miniCloseBtn = document.getElementById("pomodoro-mini-close");
            }
            
            saveState() {
                const state = {
                    timeLeft: this.timeLeft,
                    totalTime: this.totalTime,
                    isRunning: this.isRunning,
                    isPaused: this.isPaused,
                    currentMode: this.currentMode,
                    round: this.round,
                    startTime: this.startTime,
                    pausedTime: this.pausedTime,
                    isMinimized: this.isMinimized
                };
                localStorage.setItem("pomodoroState", JSON.stringify(state));
            }
            
            loadState() {
                try {
                    const savedState = localStorage.getItem("pomodoroState");
                    if (savedState) {
                        const state = JSON.parse(savedState);
                        
                        console.log("Carregando estado do Pomodoro:", state);
                        
                        // Restaurar estado básico
                        this.currentMode = state.currentMode || "focus";
                        this.round = state.round || 1;
                        this.isMinimized = state.isMinimized || false;
                        
                        // Se estava rodando, calcular tempo restante
                        if (state.isRunning && state.startTime) {
                            const now = Date.now();
                            const elapsed = Math.floor((now - state.startTime) / 1000) - state.pausedTime;
                            const remaining = state.totalTime - elapsed;
                            
                            console.log("Timer estava rodando - Tempo restante:", remaining);
                            
                            if (remaining > 0) {
                                this.timeLeft = remaining;
                                this.totalTime = state.totalTime;
                                this.startTime = now; // Atualizar startTime para agora
                                this.pausedTime = 0; // Reset pausedTime
                                this.isRunning = true;
                                this.isPaused = false;
                                
                                // Reiniciar o timer sem salvar estado (evita loop)
                                this.interval = setInterval(() => this.tick(), 1000);
                                this.updateButtons();
                                this.miniTimer.classList.remove("hidden");
                                
                                console.log("Timer reiniciado com sucesso");
                            } else {
                                // Timer expirou, completar
                                console.log("Timer expirou, completando...");
                                this.setMode(this.currentMode);
                                this.complete();
                            }
                        } else if (state.isPaused) {
                            // Restaurar estado pausado
                            console.log("Restaurando timer pausado");
                            this.timeLeft = state.timeLeft;
                            this.totalTime = state.totalTime;
                            this.isPaused = true;
                            this.isRunning = false;
                            this.startTime = null;
                            this.pausedTime = 0;
                        } else {
                            // Estado normal, restaurar modo
                            console.log("Restaurando modo normal");
                            this.setMode(this.currentMode);
                        }
                        
                        // Restaurar UI
                        if (this.isMinimized) {
                            this.minimizedBar.classList.remove("hidden");
                        }
                        
                        // Atualizar display
                        this.updateDisplay();
                        this.updateProgress();
                    }
                } catch (e) {
                    console.log("Erro ao carregar estado do Pomodoro:", e);
                    this.setMode("focus");
                }
            }
            
            bindEvents() {
                // Modal controls
                this.startBtn.addEventListener("click", () => this.start());
                this.pauseBtn.addEventListener("click", () => this.pause());
                this.resetBtn.addEventListener("click", () => this.reset());
                this.focusBtn.addEventListener("click", () => this.setMode("focus"));
                this.shortBreakBtn.addEventListener("click", () => this.setMode("shortBreak"));
                this.longBreakBtn.addEventListener("click", () => this.setMode("longBreak"));
                this.closeBtn.addEventListener("click", () => this.closeModal());
                
                // Header toggle
                this.toggleBtn.addEventListener("click", () => this.toggleModal());
                
                // Minimized bar controls
                this.miniStartBtn.addEventListener("click", () => this.start());
                this.miniPauseBtn.addEventListener("click", () => this.pause());
                this.miniResetBtn.addEventListener("click", () => this.reset());
                this.miniExpandBtn.addEventListener("click", () => this.expandModal());
                this.miniCloseBtn.addEventListener("click", () => this.closeTimer());
                
                // Close modal when clicking outside
                this.modal.addEventListener("click", (e) => {
                    if (e.target === this.modal) {
                        this.closeModal();
                    }
                });
            }
            
            toggleModal() {
                if (this.modal.classList.contains("hidden")) {
                    this.showModal();
                } else {
                    this.closeModal();
                }
            }
            
            showModal() {
                this.modal.classList.remove("hidden");
                this.isMinimized = false;
            }
            
            closeModal() {
                this.modal.classList.add("hidden");
                if (this.isRunning) {
                    this.minimizeTimer();
                }
            }
            
            expandModal() {
                this.minimizedBar.classList.add("hidden");
                this.showModal();
            }
            
            minimizeTimer() {
                this.minimizedBar.classList.remove("hidden");
                this.isMinimized = true;
                this.saveState();
            }
            
            closeTimer() {
                this.minimizedBar.classList.add("hidden");
                this.isMinimized = false;
                this.reset();
            }
            
            setMode(mode) {
                this.currentMode = mode;
                this.isRunning = false;
                this.isPaused = false;
                this.startTime = null;
                this.pausedTime = 0;
                
                switch(mode) {
                    case "focus":
                        this.timeLeft = 25 * 60;
                        this.totalTime = 25 * 60;
                        this.statusDisplay.textContent = "Foco";
                        this.miniStatus.textContent = "Foco";
                        break;
                    case "shortBreak":
                        this.timeLeft = 5 * 60;
                        this.totalTime = 5 * 60;
                        this.statusDisplay.textContent = "Pausa Curta";
                        this.miniStatus.textContent = "Pausa Curta";
                        break;
                    case "longBreak":
                        this.timeLeft = 15 * 60;
                        this.totalTime = 15 * 60;
                        this.statusDisplay.textContent = "Pausa Longa";
                        this.miniStatus.textContent = "Pausa Longa";
                        break;
                }
                
                this.updateDisplay();
                this.updateButtons();
                this.updateProgress();
                this.saveState();
            }
            
            start() {
                if (!this.isRunning) {
                    this.isRunning = true;
                    this.isPaused = false;
                    
                    // Se estava pausado, continuar de onde parou
                    if (this.isPaused) {
                        this.startTime = Date.now();
                    } else if (!this.startTime) {
                        // Novo timer
                        this.startTime = Date.now();
                        this.pausedTime = 0;
                    }
                    
                    this.interval = setInterval(() => this.tick(), 1000);
                    this.updateButtons();
                    this.miniTimer.classList.remove("hidden");
                    this.saveState();
                }
            }
            
            pause() {
                if (this.isRunning) {
                    this.isRunning = false;
                    this.isPaused = true;
                    
                    // Calcular tempo decorrido desde o último start
                    if (this.startTime) {
                        this.pausedTime += Math.floor((Date.now() - this.startTime) / 1000);
                    }
                    
                    clearInterval(this.interval);
                    this.updateButtons();
                    this.saveState();
                }
            }
            
            reset() {
                this.isRunning = false;
                this.isPaused = false;
                this.startTime = null;
                this.pausedTime = 0;
                clearInterval(this.interval);
                this.setMode(this.currentMode);
                this.updateButtons();
                this.miniTimer.classList.add("hidden");
                this.saveState();
            }
            
            tick() {
                this.timeLeft--;
                this.updateDisplay();
                this.updateProgress();
                
                if (this.timeLeft <= 0) {
                    this.complete();
                }
            }
            
            complete() {
                this.isRunning = false;
                clearInterval(this.interval);
                this.playNotification();
                
                if (this.currentMode === "focus") {
                    this.round++;
                    if (this.round <= this.maxRounds) {
                        this.setMode("shortBreak");
                        this.showNotification("Pausa Curta!", "Hora de descansar por 5 minutos.");
                    } else {
                        this.setMode("longBreak");
                        this.showNotification("Pausa Longa!", "Você completou 4 pomodoros! Descanse por 15 minutos.");
                        this.round = 1; // Reset rounds
                    }
                } else {
                    this.setMode("focus");
                    this.showNotification("Volta ao Foco!", "Hora de focar novamente por 25 minutos.");
                }
                
                this.updateButtons();
            }
            
            updateDisplay() {
                const minutes = Math.floor(this.timeLeft / 60);
                const seconds = this.timeLeft % 60;
                const timeString = `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
                
                this.timerDisplay.textContent = timeString;
                this.miniTimerDisplay.textContent = timeString;
                this.miniTimer.textContent = minutes;
                this.roundDisplay.textContent = `${this.round}/${this.maxRounds}`;
            }
            
            updateProgress() {
                const progress = ((this.totalTime - this.timeLeft) / this.totalTime) * 100;
                const circumference = 2 * Math.PI * 15.9155; // radius = 15.9155
                const strokeDashoffset = circumference - (progress / 100) * circumference;
                this.progressCircle.style.strokeDashoffset = strokeDashoffset;
            }
            
            updateButtons() {
                if (this.isRunning) {
                    this.startBtn.style.display = "none";
                    this.pauseBtn.style.display = "inline-block";
                    this.miniStartBtn.style.display = "none";
                    this.miniPauseBtn.style.display = "inline-block";
                } else {
                    this.startBtn.style.display = "inline-block";
                    this.pauseBtn.style.display = "none";
                    this.miniStartBtn.style.display = "inline-block";
                    this.miniPauseBtn.style.display = "none";
                }
            }
            
            playNotification() {
                // Create audio context for notification sound
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.3);
                } catch (e) {
                    console.log("Audio notification not supported");
                }
            }
            
            showNotification(title, message) {
                if ("Notification" in window) {
                    if (Notification.permission === "granted") {
                        new Notification(title, { body: message, icon: "/favicon.ico" });
                    } else if (Notification.permission !== "denied") {
                        Notification.requestPermission().then(permission => {
                            if (permission === "granted") {
                                new Notification(title, { body: message, icon: "/favicon.ico" });
                            }
                        });
                    }
                }
            }
        }
        
        // Initialize Pomodoro Timer when DOM is loaded
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById("pomodoro-timer")) {
                window.pomodoroTimer = new PomodoroTimer();
            }
        });
        
        // Save state when page is about to unload
        window.addEventListener("beforeunload", function() {
            if (window.pomodoroTimer) {
                window.pomodoroTimer.saveState();
            }
        });
        
        // Save state periodically (every 30 seconds)
        setInterval(function() {
            if (window.pomodoroTimer && window.pomodoroTimer.isRunning) {
                window.pomodoroTimer.saveState();
            }
        }, 30000);

        // CSRF Token Helper - Automatically include in all AJAX requests
        (function() {
            const csrfToken = document.querySelector(\'meta[name="csrf-token"]\')?.getAttribute(\'content\');

            if (csrfToken) {
                // Store token globally for easy access
                window.csrfToken = csrfToken;

                // Override fetch to automatically include CSRF token
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    options = options || {};

                    // Only add CSRF for same-origin requests that modify data
                    const method = (options.method || \'GET\').toUpperCase();
                    if ([\'POST\', \'PUT\', \'DELETE\', \'PATCH\'].includes(method)) {
                        // Add CSRF token to headers
                        options.headers = options.headers || {};
                        if (options.headers instanceof Headers) {
                            options.headers.set(\'X-CSRF-Token\', csrfToken);
                        } else {
                            options.headers[\'X-CSRF-Token\'] = csrfToken;
                        }

                        // If sending FormData, append CSRF token
                        if (options.body instanceof FormData) {
                            options.body.append(\'csrf_token\', csrfToken);
                        }
                        // If sending JSON, include CSRF in body
                        else if (options.headers[\'Content-Type\'] === \'application/json\' && typeof options.body === \'string\') {
                            try {
                                const data = JSON.parse(options.body);
                                data.csrf_token = csrfToken;
                                options.body = JSON.stringify(data);
                            } catch(e) {
                                // If parsing fails, add as header only
                            }
                        }
                    }

                    return originalFetch(url, options);
                };

                // Add CSRF token to all forms on submit
                document.addEventListener(\'submit\', function(e) {
                    const form = e.target;

                    // Only add to forms without existing CSRF token
                    if (!form.querySelector(\'input[name="csrf_token"]\')) {
                        const input = document.createElement(\'input\');
                        input.type = \'hidden\';
                        input.name = \'csrf_token\';
                        input.value = csrfToken;
                        form.appendChild(input);
                    }
                });
            }
        })();
    </script>';
    } else {
        // Layout para páginas não logadas
        if (is_callable($content)) {
            ob_start();
            $content();
            echo ob_get_clean();
        } else {
            echo $content;
        }
    }

    echo '</body>
</html>';
}
?>
