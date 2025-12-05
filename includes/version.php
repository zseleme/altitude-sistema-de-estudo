<?php
/**
 * Helper para obter informações de versão do sistema
 */

function getAppVersion() {
    // Tentar ler do version.json criado pelo deploy
    $versionFile = __DIR__ . '/../version.json';

    if (file_exists($versionFile)) {
        $versionData = json_decode(file_get_contents($versionFile), true);
        if ($versionData && isset($versionData['version'])) {
            return [
                'version' => $versionData['version'],
                'commit' => substr($versionData['commit'] ?? '', 0, 7),
                'branch' => $versionData['branch'] ?? 'unknown',
                'deployed_at' => $versionData['deployed_at'] ?? null,
                'environment' => $versionData['environment'] ?? 'Local'
            ];
        }
    }

    // Fallback: usar data do último commit via git (se disponível)
    if (file_exists(__DIR__ . '/../.git/HEAD')) {
        $head = trim(file_get_contents(__DIR__ . '/../.git/HEAD'));

        if (preg_match('/ref: (.+)/', $head, $matches)) {
            $refPath = __DIR__ . '/../.git/' . $matches[1];
            if (file_exists($refPath)) {
                $commit = substr(trim(file_get_contents($refPath)), 0, 7);
                $branch = basename($matches[1]);

                return [
                    'version' => date('Y.m.d'),
                    'commit' => $commit,
                    'branch' => $branch,
                    'deployed_at' => null,
                    'environment' => 'Dev'
                ];
            }
        }
    }

    // Fallback final: versão baseada na data
    return [
        'version' => date('Y.m.d'),
        'commit' => 'local',
        'branch' => 'dev',
        'deployed_at' => null,
        'environment' => 'Local'
    ];
}

function getAppVersionString($short = true) {
    $info = getAppVersion();

    if ($short) {
        return "v{$info['version']}";
    }

    $parts = ["v{$info['version']}"];

    if ($info['commit'] !== 'local') {
        $parts[] = $info['commit'];
    }

    if ($info['environment'] !== 'Local') {
        $parts[] = $info['environment'];
    }

    return implode(' • ', $parts);
}
?>
