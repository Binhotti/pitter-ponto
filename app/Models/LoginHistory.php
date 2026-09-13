<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class LoginHistory
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function record(
        int $userId,
        string $ipAddress,
        ?string $userAgent = null
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO login_history (
                user_id,
                ip_address,
                user_agent,
                logged_in_at
             )
             VALUES (
                :user_id,
                :ip_address,
                :user_agent,
                NOW()
             )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'ip_address' => mb_substr($ipAddress, 0, 45),
            'user_agent' => $userAgent !== null
                ? mb_substr($userAgent, 0, 255)
                : null,
        ]);
    }

    public function countToday(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*)
             FROM login_history
             WHERE DATE(logged_in_at) = CURDATE()'
        )->fetchColumn();
    }

    public function uniqueUsersToday(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(DISTINCT user_id)
             FROM login_history
             WHERE DATE(logged_in_at) = CURDATE()'
        )->fetchColumn();
    }

    public function recent(int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        return $this->db->query(
            "SELECT
                lh.id,
                lh.user_id,
                lh.ip_address,
                lh.user_agent,
                lh.logged_in_at,
                u.name,
                u.email,
                u.avatar_path,
                u.role
             FROM login_history lh
             INNER JOIN users u ON u.id = lh.user_id
             ORDER BY lh.logged_in_at DESC
             LIMIT {$limit}"
        )->fetchAll();
    }

    public function recentForUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        $stmt = $this->db->prepare(
            "SELECT *
             FROM login_history
             WHERE user_id = :user_id
             ORDER BY logged_in_at DESC
             LIMIT {$limit}"
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function lastForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM login_history
             WHERE user_id = :user_id
             ORDER BY logged_in_at DESC
             LIMIT 1'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function dailyCounts(int $days = 7): array
    {
        $days = max(1, min($days, 31));

        $sql = "
            SELECT
                DATE(logged_in_at) AS login_date,
                COUNT(*) AS total,
                COUNT(DISTINCT user_id) AS unique_users
            FROM login_history
            WHERE logged_in_at >= (CURDATE() - INTERVAL {$days} DAY)
            GROUP BY DATE(logged_in_at)
            ORDER BY login_date ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }
}
