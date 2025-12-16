<?php
// IMPORTANTE: Processar download ANTES de qualquer output para evitar corrupção do arquivo binário
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    // Limpar qualquer output buffer existente
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Autenticação manual sem includes para evitar output indesejado
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se está logado e é admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        http_response_code(403);
        die('Acesso negado');
    }

    // Carregar configuração do banco manualmente
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();
    try {
        $dbType = $db->getDbType();
        $backupFile = '';
        $filename = 'backup_' . date('Y-m-d_H-i-s');

        if ($dbType === 'sqlite') {
            // Para SQLite, usar VACUUM INTO para criar backup seguro
            $dbPath = DB_PATH;
            if (file_exists($dbPath)) {
                // Criar arquivo temporário para o backup
                $tempBackup = sys_get_temp_dir() . '/' . $filename . '.db';

                try {
                    // Usar VACUUM INTO para criar backup consistente
                    // Este método é mais seguro pois garante que o backup seja uma cópia consistente
                    $pdo = $db->getConnection();
                    $pdo->exec("VACUUM INTO " . $pdo->quote($tempBackup));

                    // Verificar se o arquivo foi criado e tem tamanho válido
                    if (file_exists($tempBackup) && filesize($tempBackup) > 0) {
                        // Limpar TODOS os buffers de output para evitar corrupção
                        while (ob_get_level()) {
                            ob_end_clean();
                        }

                        // Verificar integridade antes de enviar
                        $testPdo = new PDO('sqlite:' . $tempBackup);
                        $result = $testPdo->query("PRAGMA integrity_check")->fetch();
                        $testPdo = null;

                        if ($result[0] !== 'ok') {
                            unlink($tempBackup);
                            throw new Exception('Falha na verificação de integridade do backup gerado');
                        }

                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $filename . '.db"');
                        header('Content-Length: ' . filesize($tempBackup));
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');

                        // Enviar arquivo em modo binário
                        $handle = fopen($tempBackup, 'rb');
                        fpassthru($handle);
                        fclose($handle);

                        // Limpar arquivo temporário
                        unlink($tempBackup);
                        exit;
                    } else {
                        throw new Exception('Falha ao criar arquivo de backup');
                    }
                } catch (Exception $e) {
                    // Se VACUUM INTO falhar (SQLite antigo), usar método alternativo
                    // Fechar todas as conexões, copiar o arquivo, e reabrir
                    $pdo = null;

                    // Criar cópia do arquivo
                    if (copy($dbPath, $tempBackup)) {
                        // Limpar TODOS os buffers de output para evitar corrupção
                        while (ob_get_level()) {
                            ob_end_clean();
                        }

                        // Verificar integridade antes de enviar
                        $testPdo = new PDO('sqlite:' . $tempBackup);
                        $result = $testPdo->query("PRAGMA integrity_check")->fetch();
                        $testPdo = null;

                        if ($result[0] !== 'ok') {
                            unlink($tempBackup);
                            throw new Exception('Falha na verificação de integridade do backup gerado');
                        }

                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $filename . '.db"');
                        header('Content-Length: ' . filesize($tempBackup));
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');

                        // Enviar arquivo em modo binário
                        $handle = fopen($tempBackup, 'rb');
                        fpassthru($handle);
                        fclose($handle);

                        // Limpar arquivo temporário
                        unlink($tempBackup);
                        exit;
                    } else {
                        throw new Exception('Erro ao criar backup: ' . $e->getMessage());
                    }
                }
            } else {
                $error = 'Arquivo de banco de dados não encontrado';
            }
        } else {
            // Para PostgreSQL, criar dump SQL
            $dumpFile = sys_get_temp_dir() . '/' . $filename . '.sql';

            $command = sprintf(
                'pg_dump -h %s -p %s -U %s -d %s -n %s > %s 2>&1',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_PORT),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_NAME),
                escapeshellarg(DB_SCHEMA),
                escapeshellarg($dumpFile)
            );

            // Definir senha via variável de ambiente
            putenv('PGPASSWORD=' . DB_PASS);

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($dumpFile)) {
                // Limpar TODOS os buffers de output para evitar corrupção
                while (ob_get_level()) {
                    ob_end_clean();
                }

                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $filename . '.sql"');
                header('Content-Length: ' . filesize($dumpFile));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');

                // Enviar arquivo em modo binário
                $handle = fopen($dumpFile, 'rb');
                fpassthru($handle);
                fclose($handle);

                unlink($dumpFile);
                exit;
            } else {
                $error = 'Erro ao criar backup do PostgreSQL. Verifique se pg_dump está instalado.';
            }
        }
    } catch (Exception $e) {
        // Em caso de erro no download, redirecionar para mostrar mensagem
        session_start();
        $_SESSION['download_error'] = $e->getMessage();
        header('Location: database.php');
        exit;
    }
}

// Inicialização normal (quando não é download)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance();
$success = '';
$error = '';

// Verificar se há erro de download da sessão
if (isset($_SESSION['download_error'])) {
    $error = 'Erro ao fazer download do backup: ' . $_SESSION['download_error'];
    unset($_SESSION['download_error']);
}

// Processar upload/restore do backup
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    try {
        $file = $_FILES['backup_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo');
        }

        $dbType = $db->getDbType();

        if ($dbType === 'sqlite') {
            // Para SQLite, verificar se é arquivo .db
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileExtension !== 'db') {
                throw new Exception('O arquivo deve ter extensão .db para SQLite');
            }

            // Criar arquivo temporário para validação
            $tempFile = sys_get_temp_dir() . '/restore_temp_' . uniqid() . '.db';
            if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                throw new Exception('Erro ao processar arquivo de upload');
            }

            // Validar se o arquivo é um banco SQLite válido
            try {
                // Verificar se o arquivo tem conteúdo
                $fileSize = filesize($tempFile);
                if ($fileSize === 0) {
                    unlink($tempFile);
                    throw new Exception('O arquivo está vazio (0 bytes)');
                }

                // Verificar a assinatura do arquivo SQLite (primeiros 16 bytes devem ser "SQLite format 3\000")
                $handle = fopen($tempFile, 'rb');
                $header = fread($handle, 16);
                fclose($handle);

                if (substr($header, 0, 13) !== 'SQLite format') {
                    unlink($tempFile);
                    throw new Exception('O arquivo não é um banco SQLite válido (assinatura incorreta). Tamanho: ' . number_format($fileSize) . ' bytes. Verifique se o download foi feito corretamente.');
                }

                // Tentar abrir o banco
                $testPdo = new PDO('sqlite:' . $tempFile);
                $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Testar integridade do banco
                $result = $testPdo->query("PRAGMA integrity_check")->fetch();
                if ($result[0] !== 'ok') {
                    throw new Exception('Arquivo de backup está corrompido (falha na verificação de integridade)');
                }

                // Verificar se tem as tabelas principais
                $tables = $testPdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                if (empty($tables)) {
                    throw new Exception('Arquivo de backup não contém tabelas');
                }

                $testPdo = null; // Fechar conexão de teste
            } catch (PDOException $e) {
                unlink($tempFile);
                throw new Exception('Arquivo não é um banco SQLite válido: ' . $e->getMessage());
            }

            $dbPath = DB_PATH;
            $backupPath = $dbPath . '.backup_' . date('Y-m-d_H-i-s');

            // Fechar todas as conexões PDO ativas antes de substituir o arquivo
            $pdo = $db->getConnection();
            $pdo = null;

            // Fazer backup do arquivo atual usando VACUUM INTO (mais seguro)
            if (file_exists($dbPath)) {
                try {
                    // Tentar criar backup com VACUUM
                    $backupPdo = new PDO('sqlite:' . $dbPath);
                    $backupPdo->exec("VACUUM INTO " . $backupPdo->quote($backupPath));
                    $backupPdo = null;
                } catch (Exception $e) {
                    // Se falhar, usar cópia simples
                    if (!copy($dbPath, $backupPath)) {
                        unlink($tempFile);
                        throw new Exception('Erro ao criar backup do arquivo atual');
                    }
                }
            }

            // Substituir com o arquivo validado
            if (copy($tempFile, $dbPath)) {
                unlink($tempFile);

                // Verificar integridade do arquivo restaurado
                try {
                    $verifyPdo = new PDO('sqlite:' . $dbPath);
                    $result = $verifyPdo->query("PRAGMA integrity_check")->fetch();
                    $verifyPdo = null;

                    if ($result[0] !== 'ok') {
                        // Restaurar backup anterior
                        copy($backupPath, $dbPath);
                        throw new Exception('Falha na verificação de integridade após restauração');
                    }
                } catch (Exception $e) {
                    // Restaurar backup anterior
                    copy($backupPath, $dbPath);
                    throw new Exception('Erro ao verificar banco restaurado: ' . $e->getMessage());
                }

                $success = 'Base de dados restaurada com sucesso! Backup anterior salvo em: ' . basename($backupPath);
            } else {
                unlink($tempFile);
                throw new Exception('Erro ao mover arquivo de backup');
            }
        } else {
            // Para PostgreSQL, restaurar dump SQL
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileExtension !== 'sql') {
                throw new Exception('O arquivo deve ter extensão .sql para PostgreSQL');
            }

            $tempFile = sys_get_temp_dir() . '/' . basename($file['tmp_name']) . '.sql';

            if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                throw new Exception('Erro ao processar arquivo de backup');
            }

            // Limpar schema antes de restaurar
            $db->execute("DROP SCHEMA IF EXISTS " . DB_SCHEMA . " CASCADE");
            $db->execute("CREATE SCHEMA " . DB_SCHEMA);

            $command = sprintf(
                'psql -h %s -p %s -U %s -d %s < %s 2>&1',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_PORT),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_NAME),
                escapeshellarg($tempFile)
            );

            putenv('PGPASSWORD=' . DB_PASS);

            exec($command, $output, $returnCode);
            unlink($tempFile);

            if ($returnCode === 0) {
                $success = 'Base de dados restaurada com sucesso!';
            } else {
                throw new Exception('Erro ao restaurar backup. Verifique se psql está instalado. Erro: ' . implode("\n", $output));
            }
        }
    } catch (Exception $e) {
        $error = 'Erro ao restaurar backup: ' . $e->getMessage();
    }
}

// Obter informações sobre o banco de dados
$dbType = $db->getDbType();
$dbInfo = [];

if ($dbType === 'sqlite') {
    $dbPath = DB_PATH;
    $dbInfo['tipo'] = 'SQLite';
    $dbInfo['arquivo'] = $dbPath;
    $dbInfo['tamanho'] = file_exists($dbPath) ? filesize($dbPath) : 0;
    $dbInfo['tamanho_formatado'] = $dbInfo['tamanho'] > 0 ? number_format($dbInfo['tamanho'] / 1024, 2) . ' KB' : 'N/A';
} else {
    $dbInfo['tipo'] = 'PostgreSQL';
    $dbInfo['host'] = DB_HOST;
    $dbInfo['porta'] = DB_PORT;
    $dbInfo['banco'] = DB_NAME;
    $dbInfo['schema'] = DB_SCHEMA;
}

// Contar registros nas principais tabelas
$tables = ['usuarios', 'categorias', 'cursos', 'aulas', 'materiais'];
$dbInfo['tabelas'] = [];

foreach ($tables as $table) {
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM $table");
        $dbInfo['tabelas'][$table] = $result['total'];
    } catch (Exception $e) {
        $dbInfo['tabelas'][$table] = 0;
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Administração</span>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Base de Dados</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Gestão da Base de Dados</h1>
                    <p class="text-gray-600 mt-2">Faça backup e restaure a base de dados do sistema</p>
                </div>

                <!-- Success/Error Messages -->
                ' . ($success ? '
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-2 mt-0.5"></i>
                        <p class="text-green-700 text-sm">' . htmlspecialchars($success) . '</p>
                    </div>
                </div>' : '') . '

                ' . ($error ? '
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2 mt-0.5"></i>
                        <p class="text-red-700 text-sm">' . htmlspecialchars($error) . '</p>
                    </div>
                </div>' : '') . '

                <!-- Database Info -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-database mr-2 text-blue-600"></i>
                            Informações da Base de Dados
                        </h2>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">Tipo:</span>
                                <span class="text-sm text-gray-900 font-semibold">' . htmlspecialchars($dbInfo['tipo']) . '</span>
                            </div>

                            ' . ($dbType === 'sqlite' ? '
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">Arquivo:</span>
                                <span class="text-sm text-gray-900 font-mono text-xs">' . htmlspecialchars(basename($dbInfo['arquivo'])) . '</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">Tamanho:</span>
                                <span class="text-sm text-gray-900">' . htmlspecialchars($dbInfo['tamanho_formatado']) . '</span>
                            </div>' : '
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">Host:</span>
                                <span class="text-sm text-gray-900">' . htmlspecialchars($dbInfo['host']) . ':' . htmlspecialchars($dbInfo['porta']) . '</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">Banco:</span>
                                <span class="text-sm text-gray-900">' . htmlspecialchars($dbInfo['banco']) . '</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">Schema:</span>
                                <span class="text-sm text-gray-900">' . htmlspecialchars($dbInfo['schema']) . '</span>
                            </div>') . '
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            <i class="fas fa-table mr-2 text-blue-600"></i>
                            Estatísticas
                        </h2>

                        <div class="space-y-3">
                            ' . implode('', array_map(function($table, $count) {
                                $icons = [
                                    'usuarios' => 'fa-users',
                                    'categorias' => 'fa-folder',
                                    'cursos' => 'fa-book',
                                    'aulas' => 'fa-video',
                                    'materiais' => 'fa-file-pdf'
                                ];
                                $icon = $icons[$table] ?? 'fa-table';
                                return '
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600">
                                    <i class="fas ' . $icon . ' mr-2 text-gray-400"></i>
                                    ' . ucfirst($table) . ':
                                </span>
                                <span class="text-sm text-gray-900 font-semibold">' . number_format($count) . '</span>
                            </div>';
                            }, array_keys($dbInfo['tabelas']), $dbInfo['tabelas'])) . '
                        </div>
                    </div>
                </div>

                <!-- Actions Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Download Backup -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-download text-2xl text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Download do Backup</h3>
                                <p class="text-sm text-gray-600 mt-1">Faça o download de um backup completo da base de dados</p>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <div class="flex">
                                <i class="fas fa-info-circle text-yellow-600 mr-2 mt-0.5"></i>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-medium mb-1">Importante:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>O backup incluirá todos os dados atuais</li>
                                        <li>Guarde o arquivo em local seguro</li>
                                        <li>Formato: ' . ($dbType === 'sqlite' ? '.db (SQLite)' : '.sql (PostgreSQL)') . '</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <a href="?action=download"
                           class="inline-flex items-center justify-center w-full px-4 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Baixar Backup Agora
                        </a>
                    </div>

                    <!-- Upload/Restore Backup -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-upload text-2xl text-orange-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Restaurar Backup</h3>
                                <p class="text-sm text-gray-600 mt-1">Faça upload de um backup para restaurar a base de dados</p>
                            </div>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2 mt-0.5"></i>
                                <div class="text-sm text-red-800">
                                    <p class="font-medium mb-1">Atenção!</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Todos os dados atuais serão substituídos</li>
                                        <li>Esta ação não pode ser desfeita</li>
                                        <li>Faça um backup antes de restaurar</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" onsubmit="return confirm(\'ATENÇÃO: Todos os dados atuais serão substituídos. Você tem certeza?\');">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Arquivo de Backup (' . ($dbType === 'sqlite' ? '.db' : '.sql') . ')
                                </label>
                                <input type="file"
                                       name="backup_file"
                                       accept="' . ($dbType === 'sqlite' ? '.db' : '.sql') . '"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center justify-center w-full px-4 py-3 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                                <i class="fas fa-upload mr-2"></i>
                                Restaurar Backup
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-3">
                        <i class="fas fa-question-circle mr-2"></i>
                        Como funciona?
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-blue-900 mb-2">Download do Backup</h4>
                            <p class="text-sm text-blue-800">
                                Cria uma cópia completa da base de dados atual. Use isto regularmente para ter backups de segurança
                                ou antes de fazer alterações importantes no sistema.
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-900 mb-2">Restaurar Backup</h4>
                            <p class="text-sm text-blue-800">
                                Substitui todos os dados atuais pelos dados do arquivo de backup. Use isto para recuperar dados
                                de um backup anterior ou migrar dados entre instalações.
                            </p>
                        </div>
                    </div>
                </div>';

require_once __DIR__ . '/../includes/layout.php';
renderLayout('Base de Dados - Administração', $content, true, true);
?>
