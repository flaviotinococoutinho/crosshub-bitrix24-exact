<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$failed = false;
echo 'PHP ' . PHP_VERSION . PHP_EOL;
foreach (array('curl', 'json', 'mbstring', 'ctype') as $extension) {
    $loaded = extension_loaded($extension);
    echo $extension . ': ' . ($loaded ? 'OK' : 'AUSENTE') . PHP_EOL;
    $failed = $failed || !$loaded;
}
try {
    $webhooks = require dirname(__DIR__) . '/bootstrap.php';
    echo 'Configuracao: carregada (valores sensiveis omitidos)' . PHP_EOL;
    echo 'Logs: ' . $crosshubConfiguration['log_driver'] . PHP_EOL;
    if ($crosshubConfiguration['log_driver'] === 'file') {
        echo 'Rotacao: ' . $crosshubConfiguration['log_max_bytes'] . ' bytes, ' . $crosshubConfiguration['log_backup_count'] . ' copias por canal' . PHP_EOL;
        $directory = $crosshubConfiguration['log_directory'];
        $probe = is_dir($directory) ? $directory : dirname($directory);
        if (!is_writable($probe)) {
            echo 'Diretorio de logs: sem permissao de escrita' . PHP_EOL;
            $failed = true;
        }
    }
} catch (Exception $error) {
    echo 'Configuracao: invalida; confira os nomes e formatos de .env.example.' . PHP_EOL;
    $failed = true;
}
echo 'Nenhuma chamada externa realizada.' . PHP_EOL;
exit($failed ? 1 : 0);
