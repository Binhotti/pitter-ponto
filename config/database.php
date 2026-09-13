<?php

declare(strict_types=1);

return (function (): \PDO {
    static $pdo = null;

    if ($pdo instanceof \PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        config('database.host'),
        config('database.port'),
        config('database.database')
    );

    try {
        $pdo = new \PDO(
            $dsn,
            (string) config('database.username'),
            (string) config('database.password'),
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (\PDOException $exception) {
        http_response_code(500);

        exit(
            'Erro ao conectar com o banco de dados. ' .
            'Confira o arquivo .env e se o MySQL do XAMPP está ligado.' .
            '<br><small>' .
            e($exception->getMessage()) .
            '</small>'
        );
    }

    return $pdo;
})();