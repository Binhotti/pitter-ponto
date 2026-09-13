<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$routes = require BASE_PATH . '/routes/web.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$route = trim((string)($_GET['route'] ?? ''), '/');

$handler = $routes[$method][$route] ?? null;

if ($handler === null) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

[$controllerClass, $action] = $handler;
$controller = new $controllerClass();
$controller->{$action}();
