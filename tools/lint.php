<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$failures = 0;
$count = 0;
$directories = array('src', 'config', 'tests', 'tools', 'btxincoming', 'exactincoming');
$files = glob($root . '/*.php');
foreach ($directories as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php' && strpos($file->getFilename(), '._') !== 0) {
            $files[] = $file->getPathname();
        }
    }
}
foreach ($files as $file) {
    if (strpos(basename($file), '._') === 0) {
        continue;
    }
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
    $count++;
    if ($code !== 0) {
        echo str_replace($root . '/', '', $file) . ': sintaxe invalida' . PHP_EOL;
        $failures++;
    }
    $output = array();
}
echo $count . ' arquivos PHP verificados; ' . $failures . ' falhas.' . PHP_EOL;
exit($failures ? 1 : 0);
