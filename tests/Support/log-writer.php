<?php

require dirname(dirname(__DIR__)) . '/autoload.php';
$logger = new \CrossHub\Adapters\Logging\RotatingFileLogger($argv[1], new \CrossHub\Logging\Rotation(512, 30));
for ($number = 0; $number < 30; $number++) {
    if (!$logger->write('concurrent', 'worker-' . $argv[2] . '-record-' . $number . str_repeat('.', 35) . "\n")) {
        exit(1);
    }
}
