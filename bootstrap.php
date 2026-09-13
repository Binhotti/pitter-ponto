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
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443')
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

date_default_timezone_set(config('app.timezone', 'America/Sao_Paulo'));

/*
 * Timeout por inatividade.
 * Mantém a sessão viva enquanto o usuário está usando o sistema,
 * mas exige novo login depois do período configurado sem atividade.
 */
if (isset($_SESSION['user'])) {
    $timeoutSeconds = max(
        300,
        (int)config('security.session_timeout_minutes', 30) * 60
    );

    $lastActivity = (int)($_SESSION['last_activity'] ?? time());

    if ((time() - $lastActivity) > $timeoutSeconds) {
        unset($_SESSION['user'], $_SESSION['last_activity'], $_SESSION['login_at']);

        session_regenerate_id(true);

        $_SESSION['_flash']['error'] =
            'Sua sessão expirou por inatividade. Entre novamente.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}
