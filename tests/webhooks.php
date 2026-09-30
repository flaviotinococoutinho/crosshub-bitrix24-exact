<?php

$webhookCases = require __DIR__ . '/Fixtures/webhook-scenarios.php';
$webhookGolden = json_decode(file_get_contents(__DIR__ . '/Fixtures/webhook-requests.json'), true);
foreach ($webhookCases as $name => $case) {
    check('webhook/' . $name, function () use ($settings, $case, $webhookGolden, $name) {
        $http = new \CrossHub\Tests\RecordingHttp();
        $http->responses = $case[3];
        $logger = new \CrossHub\Tests\MemoryLogger();
        $bitrix = new \CrossHub\Integration\Bitrix($http, $logger, $settings);
        $exact = new \CrossHub\Integration\Exact($http, $settings);
        $handler = $case[0] === 'bitrix'
            ? new \CrossHub\Application\BitrixWebhook($bitrix, $exact, $logger, $settings['input_mappings'])
            : new \CrossHub\Application\ExactWebhook($bitrix, $logger);
        // Avisos de variaveis diagnosticas antigas nao fazem parte da resposta HTTP.
        set_error_handler(function () { return true; });
        try {
            ob_start();
            $handler->handle($case[1], $case[2]);
            $output = ob_get_clean();
        } finally {
            restore_error_handler();
        }
        same('', $output, 'Webhook nao deve emitir diagnosticos');
        same(array(), $http->responses, 'Respostas simuladas nao consumidas');
        same($webhookGolden[$name], normalizeCapture($http->requests), 'Ordem/payload do webhook mudou');
    });
}
