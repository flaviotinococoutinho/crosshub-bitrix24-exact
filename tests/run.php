<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/autoload.php';
require __DIR__ . '/Support/RecordingHttp.php';
require __DIR__ . '/Support/MemoryLogger.php';
require __DIR__ . '/Support/normalize.php';

$passed = 0;
$failed = 0;
function check($name, $test)
{
    global $passed, $failed;
    try {
        $test();
        $passed++;
    } catch (Exception $error) {
        $failed++;
        echo 'FAIL ' . $name . ': ' . $error->getMessage() . PHP_EOL;
    } catch (Throwable $error) {
        $failed++;
        echo 'FAIL ' . $name . ': ' . get_class($error) . PHP_EOL;
    }
}
function same($expected, $actual, $message = 'Resultado divergente')
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}
function temporaryDirectory()
{
    $path = sys_get_temp_dir() . '/crosshub-test-' . uniqid('', true);
    mkdir($path, 0700);
    return $path;
}

$crosshubEnvironment = new \CrossHub\Configuration\Environment(__DIR__ . '/Fixtures/environment.env.example');
$settings = require dirname(__DIR__) . '/config/application.php';
date_default_timezone_set($settings['timezone']);
$golden = json_decode(file_get_contents(__DIR__ . '/Fixtures/requests.json'), true);
$cases = require __DIR__ . '/Fixtures/scenarios.php';
foreach ($cases as $name => $case) {
    check('regression/' . $name, function () use ($settings, $golden, $case, $name) {
        $http = new \CrossHub\Tests\RecordingHttp();
        $http->responses = $case[3];
        $logger = new \CrossHub\Tests\MemoryLogger();
        $client = $case[0] === 'bitrix'
            ? new \CrossHub\Integration\Bitrix($http, $logger, $settings)
            : new \CrossHub\Integration\Exact($http, $settings);
        $result = call_user_func_array(array($client, $case[1]), $case[2]);
        same(array(), $http->responses, 'Respostas simuladas nao consumidas');
        same($golden[$name], normalizeCapture(array('result' => $result, 'requests' => $http->requests)), 'Contrato HTTP mudou');
    });
}

check('normalization', function () {
    same('nome@gmail.com', \CrossHub\Domain\Normalizer::email('NOME@gmail.com.br'));
    same('Ana Silva', \CrossHub\Domain\Normalizer::fullName('Ana Silva', 'Silva'));
    same('answer', \CrossHub\Domain\Normalizer::meaning('captação', array('captacao' => 'answer')));
});

check('configuration/private-mapping-selected-by-environment', function () {
    $path = temporaryDirectory();
    file_put_contents($path . '/mappings.php', '<?php return array("private-fixture" => "loaded");');
    $fixture = file_get_contents(__DIR__ . '/Fixtures/environment.env.example');
    file_put_contents($path . '/environment', $fixture . "\nBTX_MAPPINGS_FILE='" . $path . "/mappings.php'\n");
    $crosshubEnvironment = new \CrossHub\Configuration\Environment($path . '/environment');
    $customSettings = require dirname(__DIR__) . '/config/application.php';
    same(array('private-fixture' => 'loaded'), $customSettings['bitrix_mappings']);
});

check('configuration/missing-private-mapping-fails-without-path', function () {
    $path = temporaryDirectory();
    $fixture = file_get_contents(__DIR__ . '/Fixtures/environment.env.example');
    file_put_contents($path . '/environment', $fixture . "\nBTX_MAPPINGS_FILE='" . $path . "/missing-private.php'\n");
    $crosshubEnvironment = new \CrossHub\Configuration\Environment($path . '/environment');
    try {
        require dirname(__DIR__) . '/config/application.php';
    } catch (RuntimeException $error) {
        same(false, strpos($error->getMessage(), $path));
        return;
    }
    throw new RuntimeException('Deveria rejeitar o arquivo de mapeamento inexistente');
});

check('exact/json-escapes-quotes-and-line-breaks', function () use ($settings) {
    $http = new \CrossHub\Tests\RecordingHttp();
    $http->responses = array('{"success":true}');
    $exact = new \CrossHub\Integration\Exact($http, $settings);
    $text = "Empresa \"Exemplo\"\nSegunda linha \\ fim";
    $exact->createLead($text, 'person@example.invalid', $text, '111', '', 'code', '12', $text, 'Vendas');
    $payload = json_decode($http->requests[0]['body'], true);
    same(JSON_ERROR_NONE, json_last_error());
    same($text, $payload['Empresa']);
    same($text, $payload['Obs']);
    same($text, $payload['Contatos'][0]['Nome']);
});

check('environment/literal-values-and-precedence', function () {
    $path = temporaryDirectory() . '/environment';
    file_put_contents($path, "CROSSHUB_TEST_LITERAL='literal # and = and \$value'\nCROSSHUB_TEST_BOOLEAN=false\nCROSSHUB_TEST_NUMBER=0\n");
    $environment = new \CrossHub\Configuration\Environment($path);
    same('literal # and = and $value', $environment->required('CROSSHUB_TEST_LITERAL'));
    same(false, $environment->boolean('CROSSHUB_TEST_BOOLEAN', true));
    same(0, $environment->integer('CROSSHUB_TEST_NUMBER', 5, 0));
    putenv('CROSSHUB_TEST_LITERAL=external');
    same('external', $environment->required('CROSSHUB_TEST_LITERAL'));
    putenv('CROSSHUB_TEST_LITERAL');
});
check('environment/invalid-value-hides-content', function () {
    $path = temporaryDirectory() . '/environment';
    file_put_contents($path, "CROSSHUB_TEST_BOOLEAN=invalid-private-value\n");
    $environment = new \CrossHub\Configuration\Environment($path);
    try {
        $environment->boolean('CROSSHUB_TEST_BOOLEAN', false);
    } catch (RuntimeException $error) {
        same(false, strpos($error->getMessage(), 'invalid-private-value'));
        return;
    }
    throw new RuntimeException('Deveria rejeitar booleano invalido');
});

check('environment/bom-and-unquoted-unicode', function () {
    $path = temporaryDirectory() . '/environment';
    file_put_contents($path, "\xEF\xBB\xBFCROSSHUB_TEST_UNICODE=café¿\n");
    $environment = new \CrossHub\Configuration\Environment($path);
    same('café¿', $environment->required('CROSSHUB_TEST_UNICODE'));
});

check('logging/rotation-bounded-and-keeps-newest', function () {
    $path = temporaryDirectory();
    $logger = new \CrossHub\Adapters\Logging\RotatingFileLogger($path, new \CrossHub\Logging\Rotation(256, 2));
    for ($i = 1; $i <= 6; $i++) {
        same(true, $logger->write('channel', 'entry-' . $i . str_repeat('.', 220)));
    }
    same(4, count(glob($path . '/*')), 'Atual, duas copias e um lock esperados');
    same(true, strpos(file_get_contents($path . '/channel.log'), 'entry-6') === 0);
    same(true, strpos(file_get_contents($path . '/channel.log.2'), 'entry-4') === 0);
    foreach (glob($path . '/*') as $file) {
        same(true, filesize($file) <= 256, 'Limite de bytes excedido');
    }
});
check('logging/oversized-record-and-zero-backups', function () {
    $path = temporaryDirectory();
    $logger = new \CrossHub\Adapters\Logging\RotatingFileLogger($path, new \CrossHub\Logging\Rotation(256, 0));
    same(true, $logger->write('channel', str_repeat('ç', 1000)));
    same(true, $logger->write('channel', 'newest'));
    same('newest', file_get_contents($path . '/channel.log'));
    same(2, count(glob($path . '/*')));
});
check('logging/reduced-retention', function () {
    $path = temporaryDirectory();
    foreach (range(1, 4) as $number) { file_put_contents($path . '/channel.log.' . $number, 'old'); }
    $logger = new \CrossHub\Adapters\Logging\RotatingFileLogger($path, new \CrossHub\Logging\Rotation(256, 1));
    same(true, $logger->write('channel', 'newest'));
    same(3, count(glob($path . '/*')));
});
check('logging/credentials-redacted', function () {
    $memory = new \CrossHub\Tests\MemoryLogger();
    $logger = new \CrossHub\Logging\RedactingLogger($memory, array('test-secret-token', 'https://bitrix.invalid/rest/1/test-secret-token'));
    $logger->write('channel', 'test-secret-token ' . json_encode('https://bitrix.invalid/rest/1/test-secret-token'));
    same(false, strpos($memory->entries[0]['message'], 'test-secret-token'));
});

require __DIR__ . '/webhooks.php';
require __DIR__ . '/adapters.php';
echo $passed . ' verificacoes passaram; ' . $failed . ' falharam.' . PHP_EOL;
exit($failed ? 1 : 0);
