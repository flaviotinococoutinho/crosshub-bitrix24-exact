<?php

// Uso: php -S 127.0.0.1:8080 tools/dev-router.php (na raiz do projeto).
// Lista positiva de rotas para impedir exposicao de .env, fontes e arquivos locais.
$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routes = array(
    '/' => '/index.php',
    '/index.php' => '/index.php',
    '/btxincoming/' => '/btxincoming/index.php',
    '/btxincoming/index.php' => '/btxincoming/index.php',
    '/exactincoming/' => '/exactincoming/index.php',
    '/exactincoming/index.php' => '/exactincoming/index.php',
);
if (!isset($routes[$path])) {
    http_response_code(404);
    exit;
}
require $root . $routes[$path];
