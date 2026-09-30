<?php

require_once __DIR__ . '/autoload.php';

$crosshubEnvFile = getenv('CROSSHUB_ENV_FILE');
$crosshubEnvironment = new \CrossHub\Configuration\Environment($crosshubEnvFile === false ? __DIR__ . '/.env' : $crosshubEnvFile);
$crosshubConfiguration = require __DIR__ . '/config/application.php';
date_default_timezone_set($crosshubConfiguration['timezone']);

$crosshubFactory = new \CrossHub\Application\Factory($crosshubConfiguration);
return $crosshubFactory->webhooks();
