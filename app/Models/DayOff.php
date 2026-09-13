<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class DayOff
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function findForDate(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM day_offs
             WHERE user_id = :user_id
               AND :date BETWEEN start_date AND end_date
             ORDER BY id DESC
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);

        return $stmt->fetch() ?: null;
    }
}
