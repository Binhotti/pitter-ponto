<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Holiday
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function ensureYear(int $year): void
    {
        $holidays = [
            sprintf('%04d-01-01', $year) => 'Confraternização Universal',
            sprintf('%04d-04-21', $year) => 'Tiradentes',
            sprintf('%04d-05-01', $year) => 'Dia do Trabalho',
            sprintf('%04d-09-07', $year) => 'Independência do Brasil',
            sprintf('%04d-10-12', $year) => 'Nossa Senhora Aparecida',
            sprintf('%04d-11-02', $year) => 'Finados',
            sprintf('%04d-11-15', $year) => 'Proclamação da República',
            sprintf('%04d-11-20', $year) => 'Dia da Consciência Negra',
            sprintf('%04d-12-25', $year) => 'Natal',
        ];

        $stmt = $this->db->prepare(
            'INSERT INTO holidays (
                holiday_date,
                name,
                overtime_percent
            )
            VALUES (
                :holiday_date,
                :name,
                100
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                overtime_percent = VALUES(overtime_percent)'
        );

        foreach ($holidays as $date => $name) {
            $stmt->execute([
                'holiday_date' => $date,
                'name' => $name,
            ]);
        }
    }

    public function findByDate(string $date): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM holidays
             WHERE holiday_date = :holiday_date
             LIMIT 1'
        );

        $stmt->execute(['holiday_date' => $date]);

        return $stmt->fetch() ?: null;
    }

    public function between(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM holidays
             WHERE holiday_date BETWEEN :from_date AND :to_date
             ORDER BY holiday_date'
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to,
        ]);

        return $stmt->fetchAll();
    }
}
