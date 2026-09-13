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
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, daily_minutes, monthly_hours)
             VALUES (:name, :email, :password, "employee", 528, 220)'
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
            'SELECT id, name, email, role, daily_minutes, monthly_hours, salary, created_at
             FROM users ORDER BY name'
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
            'salary' => $data['salary'] !== '' ? $data['salary'] : null,
        ]);
    }
}
