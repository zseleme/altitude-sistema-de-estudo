<?php
/**
 * Ferramenta de diagnóstico para inspecionar arquivos de backup SQLite
 * Use para verificar se o arquivo de backup foi corrompido durante o download
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
requireAdmin();

$info = [];
$error = '';

// Processar análise de arquivo
if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
    CSRFHelper::validateRequest(false);
    $file = $_FILES['backup_file'];
    $tempFile = $file['tmp_name'];

    $info['nome'] = $file['name'];
    $info['tamanho'] = filesize($tempFile);
    $info['tamanho_formatado'] = number_format($info['tamanho']) . ' bytes';

    // Ler primeiros 100 bytes do arquivo
    $handle = fopen($tempFile, 'rb');
    $header = fread($handle, 100);
    fclose($handle);

    $info['primeiros_16_bytes'] = bin2hex(substr($header, 0, 16));
    $info['primeiros_16_bytes_texto'] = substr($header, 0, 16);

    // Verificar assinatura SQLite
    $sqliteSignature = 'SQLite format 3';
    $info['tem_assinatura_sqlite'] = substr($header, 0, 13) === $sqliteSignature;

    // Mostrar primeiros 200 caracteres como texto (para detectar HTML/PHP)
    $info['primeiros_200_chars'] = substr($header, 0, min(200, strlen($header)));

    // Verificar se parece HTML
    $info['parece_html'] = strpos($header, '<!DOCTYPE') !== false ||
                           strpos($header, '<html') !== false ||
                           strpos($header, '<?php') !== false;

    // Tentar abrir como SQLite
    try {
        $testPdo = new PDO('sqlite:' . $tempFile);
        $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Testar integridade
        $result = $testPdo->query("PRAGMA integrity_check")->fetch();
        $info['integrity_check'] = $result[0];

        // Listar tabelas
        $tables = $testPdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $info['tabelas'] = $tables;
        $info['num_tabelas'] = count($tables);

        $testPdo = null;
        $info['erro_sqlite'] = null;
    } catch (PDOException $e) {
        $info['erro_sqlite'] = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/layout.php';

ob_start();
?>

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
                <a href="/admin/database.php" class="text-sm font-medium text-gray-700 hover:text-blue-600">Base de Dados</a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Debug Backup</span>
            </div>
        </li>
    </ol>
</nav>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Diagnóstico de Arquivo de Backup</h1>
    <p class="text-gray-600 mt-2">Ferramenta para inspecionar e diagnosticar problemas com arquivos de backup SQLite</p>
</div>

<!-- Upload Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">
        <i class="fas fa-upload mr-2 text-blue-600"></i>
        Enviar Arquivo para Análise
    </h2>

    <form method="POST" enctype="multipart/form-data">
        <?php echo CSRFHelper::getTokenField(); ?>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Arquivo de Backup (.db)
            </label>
            <input type="file"
                   name="backup_file"
                   accept=".db"
                   required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <i class="fas fa-search mr-2"></i>
            Analisar Arquivo
        </button>
    </form>
</div>

<?php if (!empty($info)): ?>
<!-- Results -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-600"></i>
        Resultados da Análise
    </h2>

    <div class="space-y-4">
        <!-- Status Overall -->
        <div class="p-4 rounded-lg <?= $info['tem_assinatura_sqlite'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <div class="flex items-center">
                <i class="fas <?= $info['tem_assinatura_sqlite'] ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600' ?> text-2xl mr-3"></i>
                <div>
                    <h3 class="font-semibold <?= $info['tem_assinatura_sqlite'] ? 'text-green-900' : 'text-red-900' ?>">
                        <?= $info['tem_assinatura_sqlite'] ? 'Arquivo SQLite Válido' : 'Arquivo NÃO é SQLite Válido' ?>
                    </h3>
                    <p class="text-sm <?= $info['tem_assinatura_sqlite'] ? 'text-green-700' : 'text-red-700' ?>">
                        <?= $info['tem_assinatura_sqlite'] ? 'A assinatura SQLite foi encontrada' : 'A assinatura SQLite NÃO foi encontrada' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Informações Básicas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">Nome do Arquivo</h4>
                <p class="text-sm text-gray-600 font-mono"><?= htmlspecialchars($info['nome']) ?></p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">Tamanho</h4>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($info['tamanho_formatado']) ?></p>
            </div>
        </div>

        <!-- Assinatura Hexadecimal -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">Primeiros 16 Bytes (Hexadecimal)</h4>
            <p class="text-sm text-gray-600 font-mono break-all"><?= htmlspecialchars($info['primeiros_16_bytes']) ?></p>
            <p class="text-xs text-gray-500 mt-2">Esperado: 53514c69746520666f726d6174203300 ("SQLite format 3\0")</p>
        </div>

        <!-- Conteúdo como Texto -->
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">Primeiros 200 Caracteres (como texto)</h4>
            <pre class="text-xs text-gray-600 font-mono bg-gray-50 p-3 rounded overflow-x-auto"><?= htmlspecialchars($info['primeiros_200_chars']) ?></pre>
        </div>

        <?php if ($info['parece_html']): ?>
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-0.5"></i>
                <div>
                    <h4 class="font-medium text-yellow-900 mb-1">Possível Corrupção Detectada</h4>
                    <p class="text-sm text-yellow-800">
                        O arquivo parece conter HTML ou PHP ao invés de dados binários SQLite.
                        Isso geralmente indica que o download foi corrompido por output buffers ou erros PHP.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tentativa de Abrir como SQLite -->
        <?php if ($info['erro_sqlite']): ?>
        <div class="border border-red-200 rounded-lg p-4 bg-red-50">
            <h4 class="font-medium text-red-900 mb-2">Erro ao Abrir como SQLite</h4>
            <p class="text-sm text-red-700 font-mono"><?= htmlspecialchars($info['erro_sqlite']) ?></p>
        </div>
        <?php else: ?>
        <div class="border border-green-200 rounded-lg p-4 bg-green-50">
            <h4 class="font-medium text-green-900 mb-2">SQLite Aberto com Sucesso</h4>

            <div class="space-y-2 mt-3">
                <div>
                    <span class="text-sm font-medium text-green-900">Integridade:</span>
                    <span class="text-sm text-green-700 ml-2"><?= htmlspecialchars($info['integrity_check']) ?></span>
                </div>

                <div>
                    <span class="text-sm font-medium text-green-900">Número de Tabelas:</span>
                    <span class="text-sm text-green-700 ml-2"><?= $info['num_tabelas'] ?></span>
                </div>

                <?php if (!empty($info['tabelas'])): ?>
                <div>
                    <span class="text-sm font-medium text-green-900">Tabelas Encontradas:</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php foreach ($info['tabelas'] as $table): ?>
                        <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                            <i class="fas fa-table mr-1"></i>
                            <?= htmlspecialchars($table) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Soluções Sugeridas -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-blue-900 mb-3">
        <i class="fas fa-lightbulb mr-2"></i>
        Soluções Sugeridas
    </h3>

    <?php if (!$info['tem_assinatura_sqlite']): ?>
    <div class="space-y-3 text-sm text-blue-800">
        <p class="font-medium">O arquivo não é um banco SQLite válido. Possíveis causas:</p>
        <ol class="list-decimal list-inside space-y-2 ml-2">
            <li>
                <strong>Download corrompido:</strong> Tente fazer o download do backup novamente diretamente da página de Base de Dados.
            </li>
            <li>
                <strong>Arquivo errado:</strong> Verifique se você está enviando o arquivo de backup correto (.db).
            </li>
            <li>
                <strong>Navegador adicionou conteúdo:</strong> Alguns navegadores ou extensões podem modificar downloads. Tente usar outro navegador.
            </li>
            <li>
                <strong>Backup criado incorretamente:</strong> Se o problema persistir, pode haver um problema na geração do backup.
                Reporte o problema ao administrador do sistema.
            </li>
        </ol>
    </div>
    <?php else: ?>
    <p class="text-sm text-blue-800">
        <i class="fas fa-check mr-1"></i>
        O arquivo parece estar correto! Você pode tentar restaurá-lo na página de <a href="database.php" class="underline font-medium">Base de Dados</a>.
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
renderLayout('Debug Backup - Administração', $content, true, true);
?>
