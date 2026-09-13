<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/config/app.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set(config('app.timezone', 'America/Sao_Paulo'));
