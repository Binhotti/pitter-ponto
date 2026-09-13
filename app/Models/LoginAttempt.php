<?php

declare(strict_types=1);

namespace App\Models;

use DateInterval;
use DateTimeImmutable;
use PDO;

class LoginAttempt
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function status(string $email, string $ip): array
    {
        $row = $this->find($email, $ip);

        if (!$row) {
            return [
                'locked' => false,
                'remaining_seconds' => 0,
                'attempts' => 0,
            ];
        }

        $now = new DateTimeImmutable('now');

        if (!empty($row['locked_until'])) {
            $lockedUntil = new DateTimeImmutable((string)$row['locked_until']);

            if ($lockedUntil > $now) {
                return [
                    'locked' => true,
                    'remaining_seconds' => max(
                        1,
                        $lockedUntil->getTimestamp() - $now->getTimestamp()
                    ),
                    'attempts' => (int)$row['attempts'],
                ];
            }
        }

        return [
            'locked' => false,
            'remaining_seconds' => 0,
            'attempts' => (int)$row['attempts'],
        ];
    }

    public function recordFailure(
        string $email,
        string $ip,
        int $maxAttempts,
        int $windowMinutes,
        int $lockMinutes
    ): array {
        $now = new DateTimeImmutable('now');
        $row = $this->find($email, $ip);

        $attempts = 1;
        $firstAttemptAt = $now;

        if ($row) {
            $existingFirst = new DateTimeImmutable((string)$row['first_attempt_at']);
            $windowStart = $now->sub(
                new DateInterval('PT' . max(1, $windowMinutes) . 'M')
            );

            if ($existingFirst >= $windowStart) {
                $attempts = (int)$row['attempts'] + 1;
                $firstAttemptAt = $existingFirst;
            }
        }

        $lockedUntil = null;

        if ($attempts >= max(1, $maxAttempts)) {
            $lockedUntil = $now->add(
                new DateInterval('PT' . max(1, $lockMinutes) . 'M')
            );
        }

        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (
                email,
                ip_address,
                attempts,
                first_attempt_at,
                last_attempt_at,
                locked_until
             )
             VALUES (
                :email,
                :ip_address,
                :attempts,
                :first_attempt_at,
                :last_attempt_at,
                :locked_until
             )
             ON DUPLICATE KEY UPDATE
                attempts = VALUES(attempts),
                first_attempt_at = VALUES(first_attempt_at),
                last_attempt_at = VALUES(last_attempt_at),
                locked_until = VALUES(locked_until)'
        );

        $stmt->execute([
            'email' => $email,
            'ip_address' => $ip,
            'attempts' => $attempts,
            'first_attempt_at' => $firstAttemptAt->format('Y-m-d H:i:s'),
            'last_attempt_at' => $now->format('Y-m-d H:i:s'),
            'locked_until' => $lockedUntil?->format('Y-m-d H:i:s'),
        ]);

        return [
            'locked' => $lockedUntil !== null,
            'attempts' => $attempts,
            'remaining_seconds' => $lockedUntil
                ? $lockedUntil->getTimestamp() - $now->getTimestamp()
                : 0,
        ];
    }

    public function clear(string $email, string $ip): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM login_attempts
             WHERE email = :email
               AND ip_address = :ip_address'
        );

        $stmt->execute([
            'email' => $email,
            'ip_address' => $ip,
        ]);
    }

    public function cleanup(): void
    {
        $this->db->exec(
            'DELETE FROM login_attempts
             WHERE last_attempt_at < (NOW() - INTERVAL 7 DAY)'
        );
    }

    private function find(string $email, string $ip): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM login_attempts
             WHERE email = :email
               AND ip_address = :ip_address
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email,
            'ip_address' => $ip,
        ]);

        return $stmt->fetch() ?: null;
    }
}
