<?php

namespace CrossHub\Adapters\Logging;

use CrossHub\Ports\Logger;

/** Um evento JSON por linha; retencao delegada ao ambiente de execucao. */
final class StderrLogger implements Logger
{
    public function write($channel, $message)
    {
        $entry = json_encode(array('channel' => $channel, 'message' => $message));
        if ($entry === false) {
            error_log('CrossHub: nao foi possivel serializar o log operacional.');
            return false;
        }
        return @file_put_contents('php://stderr', $entry . PHP_EOL, FILE_APPEND) !== false;
    }
}
