<?php

declare(strict_types=1);

function loadEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        $values[$key] = $value;
    }

    return $values;
}

$GLOBALS['env'] = array_merge(
    loadEnvFile(BASE_PATH . '/.env.example'),
    loadEnvFile(BASE_PATH . '/.env')
);

$GLOBALS['config'] = [
    'app' => [
        'name' => $GLOBALS['env']['APP_NAME'] ?? 'Pitter Ponto',
        'url' => rtrim($GLOBALS['env']['APP_URL'] ?? 'http://localhost/pitter-ponto/public', '/'),
        'timezone' => $GLOBALS['env']['APP_TIMEZONE'] ?? 'America/Sao_Paulo',
    ],
    'security' => [
        'max_login_attempts' => (int)($GLOBALS['env']['SECURITY_MAX_LOGIN_ATTEMPTS'] ?? 5),
        'login_window_minutes' => (int)($GLOBALS['env']['SECURITY_LOGIN_WINDOW_MINUTES'] ?? 15),
        'login_lock_minutes' => (int)($GLOBALS['env']['SECURITY_LOGIN_LOCK_MINUTES'] ?? 5),
        'session_timeout_minutes' => (int)($GLOBALS['env']['SECURITY_SESSION_TIMEOUT_MINUTES'] ?? 480),
    ],
    'database' => [
        'host' => $GLOBALS['env']['DB_HOST'] ?? '127.0.0.1',
        'port' => $GLOBALS['env']['DB_PORT'] ?? '4406',
        'database' => $GLOBALS['env']['DB_DATABASE'] ?? 'pitter_ponto',
        'username' => $GLOBALS['env']['DB_USERNAME'] ?? 'root',
        'password' => $GLOBALS['env']['DB_PASSWORD'] ?? '',
    ],
];

function config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = $GLOBALS['config'];

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function url(string $route = ''): string
{
    $base = config('app.url', '');
    return $route === '' ? $base : $base . '/?route=' . ltrim($route, '/');
}

function asset(string $path): string
{
    return config('app.url', '') . '/assets/' . ltrim($path, '/');
}

function redirect(string $route): never
{
    header('Location: ' . url($route));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function authUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    return (authUser()['role'] ?? null) === 'admin';
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $message;
}
