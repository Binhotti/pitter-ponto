<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (
                name,
                email,
                password,
                role,
                daily_minutes,
                monthly_hours,
                workday_start,
                workday_end,
                lunch_minutes,
                theme,
                notifications_enabled,
                browser_notifications
             )
             VALUES (
                :name,
                :email,
                :password,
                "employee",
                528,
                220,
                "08:00:00",
                "17:48:00",
                60,
                "light",
                1,
                0
             )'
        );

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT
                id,
                name,
                email,
                role,
                daily_minutes,
                monthly_hours,
                salary,
                created_at
             FROM users
             ORDER BY name'
        )->fetchAll();
    }

    public function updateProfile(int $id, array $data): void
    {
        $sql = 'UPDATE users
                SET name = :name,
                    email = :email,
                    salary = :salary
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'salary' => $data['salary'] !== ''
                ? $data['salary']
                : null,
        ]);
    }

    public function updatePersonalSettings(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET workday_start = :workday_start,
                 workday_end = :workday_end,
                 lunch_minutes = :lunch_minutes,
                 daily_minutes = :daily_minutes,
                 theme = :theme,
                 notifications_enabled = :notifications_enabled,
                 browser_notifications = :browser_notifications
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'workday_start' => $data['workday_start'],
            'workday_end' => $data['workday_end'],
            'lunch_minutes' => $data['lunch_minutes'],
            'daily_minutes' => $data['daily_minutes'],
            'theme' => $data['theme'],
            'notifications_enabled' => $data['notifications_enabled'],
            'browser_notifications' => $data['browser_notifications'],
        ]);
    }

    public function updateAvatar(int $id, ?string $avatarPath): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET avatar_path = :avatar_path
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'avatar_path' => $avatarPath,
        ]);
    }
}
