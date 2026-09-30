<?php

$webhooks = require dirname(__DIR__) . '/bootstrap.php';
$webhooks['exact']->handle($_GET, file_get_contents('php://input'));
