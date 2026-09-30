<?php

$webhooks = require dirname(__DIR__) . '/bootstrap.php';
$webhooks['bitrix']->handle($_GET);
