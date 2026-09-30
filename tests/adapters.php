<?php

check('logging/concurrent-processes', function () {
    $path = temporaryDirectory();
    $workers = array();
    for ($worker = 1; $worker <= 6; $worker++) {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/Support/log-writer.php') . ' ' . escapeshellarg($path) . ' ' . $worker;
        $pipes = array();
        $process = proc_open($command, array(0=>array('pipe','r'), 1=>array('pipe','w'), 2=>array('pipe','w')), $pipes);
        fclose($pipes[0]);
        $workers[] = array($process, $pipes);
    }
    foreach ($workers as $worker) {
        stream_get_contents($worker[1][1]);
        stream_get_contents($worker[1][2]);
        fclose($worker[1][1]);
        fclose($worker[1][2]);
        same(0, proc_close($worker[0]), 'Processo de log falhou');
    }
    $records = array();
    foreach (glob($path . '/concurrent.log*') as $file) {
        if (substr($file, -5) === '.lock') { continue; }
        same(true, filesize($file) <= 512, 'Arquivo excedeu limite');
        $records = array_merge($records, file($file, FILE_IGNORE_NEW_LINES));
    }
    same(180, count($records), 'Registro perdido ou intercalado');
    same(180, count(array_unique($records)), 'Registro duplicado');
});

function withLocalServer($router, $test)
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $command = escapeshellarg(PHP_BINARY) . ' -S ' . $address . ' ' . escapeshellarg($router);
    $path = temporaryDirectory();
    $process = proc_open($command, array(0=>array('pipe','r'), 1=>array('file',$path.'/out','w'), 2=>array('file',$path.'/err','w')), $pipes, dirname(__DIR__));
    fclose($pipes[0]);
    try {
        $ready = false;
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $probe = @stream_socket_client('tcp://' . $address, $code, $message, 0.1);
            if ($probe !== false) { fclose($probe); $ready = true; break; }
            usleep(20000);
        }
        same(true, $ready, 'Servidor local indisponivel');
        $test('http://' . $address);
    } finally {
        proc_terminate($process);
        proc_close($process);
    }
}

check('http/real-curl-method-headers-body-and-error', function () {
    withLocalServer(__DIR__ . '/Support/echo-http.php', function ($url) {
        $http = new \CrossHub\Adapters\Http\CurlHttpClient(2);
        foreach (array('GET', 'POST', 'PUT') as $method) {
            $body = $method === 'GET' ? null : 'a=one&b=two';
            $response = $http->send(new \CrossHub\Http\Request($method, $url . '/?fail=1', $body, array('token_exact:test-token')));
            same(array('method'=>$method, 'body'=>$body === null ? '' : $body, 'token'=>'test-token'), json_decode($response, true));
        }
    });
});

check('web/private-files-unreachable', function () {
    withLocalServer(dirname(__DIR__) . '/tools/dev-router.php', function ($url) {
        foreach (array('/.env','/.git/config','/config/application.php','/storage/example.log','/tests/run.php','/tools/doctor.php','/src/Integration/Bitrix.php') as $path) {
            $curl = curl_init($url . $path);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $body = curl_exec($curl);
            same(404, curl_getinfo($curl, CURLINFO_HTTP_CODE), 'Arquivo privado acessivel');
            same('', $body);
            curl_close($curl);
        }
    });
});
