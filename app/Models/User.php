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


    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM users
             WHERE email = :email
               AND id <> :user_id
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email,
            'user_id' => $userId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function nameExists(string $name, ?int $exceptUserId = null): bool
    {
        $sql = '
            SELECT 1
            FROM users
            WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
        ';

        $params = ['name' => $name];

        if ($exceptUserId !== null) {
            $sql .= ' AND id <> :except_user_id';
            $params['except_user_id'] = $exceptUserId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
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
                lunch_start_time,
                lunch_minutes,
                theme,
                notifications_enabled,
                browser_notifications,
                notification_before_minutes,
                notification_after_minutes,
                notify_entry_enabled,
                notify_lunch_start_enabled,
                notify_lunch_return_enabled,
                notify_clock_out_enabled
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
                "12:00:00",
                60,
                "light",
                1,
                0,
                10,
                5,
                1,
                1,
                1,
                1
             )'
        );

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int)$this->db->lastInsertId();
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
                workday_start,
                workday_end,
                lunch_minutes,
                avatar_path,
                is_active,
                created_at,
                updated_at
             FROM users
             ORDER BY name'
        )->fetchAll();
    }

    public function activeUsers(): array
    {
        return $this->db->query(
            'SELECT *
             FROM users
             WHERE is_active = 1
             ORDER BY name'
        )->fetchAll();
    }

    public function countAll(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*) FROM users'
        )->fetchColumn();
    }

    public function countActive(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*) FROM users WHERE is_active = 1'
        )->fetchColumn();
    }

    public function countAdmins(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*)
             FROM users
             WHERE role = "admin"
               AND is_active = 1'
        )->fetchColumn();
    }

    public function updateByAdmin(int $id, array $data): void
    {
        $sql = '
            UPDATE users
            SET name = :name,
                email = :email,
                role = :role,
                is_active = :is_active
        ';

        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ];

        if (!empty($data['password'])) {
            $sql .= ', password = :password';
            $params['password'] = password_hash(
                (string)$data['password'],
                PASSWORD_DEFAULT
            );
        }

        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM users WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);
    }

    public function updateProfile(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 salary = :salary
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'salary' => $data['salary'] !== '' ? $data['salary'] : null,
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
                 browser_notifications = :browser_notifications,
                 notification_before_minutes = :notification_before_minutes,
                 notification_after_minutes = :notification_after_minutes,
                 notify_entry_enabled = :notify_entry_enabled,
                 notify_lunch_start_enabled = :notify_lunch_start_enabled,
                 notify_lunch_return_enabled = :notify_lunch_return_enabled,
                 notify_clock_out_enabled = :notify_clock_out_enabled
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
            'notification_before_minutes' => $data['notification_before_minutes'],
            'notification_after_minutes' => $data['notification_after_minutes'],
            'notify_entry_enabled' => $data['notify_entry_enabled'],
            'notify_lunch_start_enabled' => $data['notify_lunch_start_enabled'],
            'notify_lunch_return_enabled' => $data['notify_lunch_return_enabled'],
            'notify_clock_out_enabled' => $data['notify_clock_out_enabled'],
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
