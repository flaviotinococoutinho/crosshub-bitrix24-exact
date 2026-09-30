<?php

// Carregado pelo bootstrap. O ambiente do processo tem precedencia sobre .env.
$crosshubLogDir = $crosshubEnvironment->value('LOG_DIR', 'storage/logs');
if (substr($crosshubLogDir, 0, 1) !== '/') {
    $crosshubLogDir = dirname(__DIR__) . '/' . $crosshubLogDir;
}
$crosshubMappingsFile = $crosshubEnvironment->value('BTX_MAPPINGS_FILE', __DIR__ . '/bitrix-mappings.php');
if (substr($crosshubMappingsFile, 0, 1) !== '/') {
    $crosshubMappingsFile = dirname(__DIR__) . '/' . $crosshubMappingsFile;
}
if (!is_file($crosshubMappingsFile)) {
    throw new \RuntimeException('BTX_MAPPINGS_FILE deve apontar para um arquivo de configuracao existente.');
}

return array(
    'timezone' => $crosshubEnvironment->value('APP_TIMEZONE', 'America/Sao_Paulo'),
    'bitrix_hook' => $crosshubEnvironment->required('BTX_HOOK'),
    'bitrix_mappings' => require $crosshubMappingsFile,
    'input_mappings' => require __DIR__ . '/input-mappings.php',
    'bitrix_verify_peer' => $crosshubEnvironment->boolean('BTX_SSL_VERIFY_PEER', false),
    'http_timeout' => $crosshubEnvironment->integer('HTTP_TIMEOUT_SECONDS', 0, 0),
    'log_driver' => $crosshubEnvironment->value('LOG_DRIVER', 'file'),
    'log_directory' => $crosshubLogDir,
    'log_max_bytes' => $crosshubEnvironment->integer('LOG_MAX_BYTES', 10485760, 256),
    'log_backup_count' => $crosshubEnvironment->integer('LOG_BACKUP_COUNT', 5, 0),
    'exact_tokens' => array(
        'Aluguel' => $crosshubEnvironment->required('EXACT_TOKEN_ALUGUEL'),
        'Vendas' => $crosshubEnvironment->required('EXACT_TOKEN_VENDAS'),
        'Captacao Locacao' => $crosshubEnvironment->required('EXACT_TOKEN_CAPTACAO_LOCACAO'),
        'Captação Locação' => $crosshubEnvironment->required('EXACT_TOKEN_CAPTACAO_LOCACAO'),
        'Captacao Vendas' => $crosshubEnvironment->required('EXACT_TOKEN_CAPTACAO_VENDAS'),
        'Captação Vendas' => $crosshubEnvironment->required('EXACT_TOKEN_CAPTACAO_VENDAS'),
        'Aluguel Especial' => $crosshubEnvironment->required('EXACT_TOKEN_ALUGUEL_ESPECIAL'),
        'Aluguel_Especial' => $crosshubEnvironment->required('EXACT_TOKEN_ALUGUEL_ESPECIAL'),
    ),
    'exact_urls' => array(
        'add' => $crosshubEnvironment->value('EXACT_ADD_URL', 'https://api.spotter.exactsales.com.br/api/v2/leads'),
        'get' => $crosshubEnvironment->value('EXACT_GET_URL', 'https://api.exactsales.com.br/v2/listarlead?id='),
        'recover' => $crosshubEnvironment->value('EXACT_RECOVER_URL', 'https://api.exactsales.com.br/v2/recuperarLead'),
        'update' => $crosshubEnvironment->value('EXACT_UPDATE_URL', 'https://api.exactsales.com.br/v2/editarlead'),
    ),
);
