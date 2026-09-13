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

    public function findOwned(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM day_offs
             WHERE id = :id
               AND user_id = :user_id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function allForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM day_offs
             WHERE user_id = :user_id
             ORDER BY start_date DESC, id DESC'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function searchForUser(
        int $userId,
        string $query,
        int $limit = 20
    ): array {
        $limit = max(1, min($limit, 50));

        $stmt = $this->db->prepare(
            "SELECT *
             FROM day_offs
             WHERE user_id = :user_id
               AND (
                    type LIKE :query_type
                    OR note LIKE :query_note
               )
             ORDER BY start_date DESC
             LIMIT {$limit}"
        );

        $search = '%' . $query . '%';

        $stmt->execute([
            'user_id' => $userId,
            'query_type' => $search,
            'query_note' => $search,
        ]);

        return $stmt->fetchAll();
    }

    public function create(
        int $userId,
        string $type,
        string $startDate,
        string $endDate,
        ?string $note
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO day_offs (
                user_id,
                type,
                start_date,
                end_date,
                note
            )
            VALUES (
                :user_id,
                :type,
                :start_date,
                :end_date,
                :note
            )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'note' => $note !== '' ? $note : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        int $userId,
        string $type,
        string $startDate,
        string $endDate,
        ?string $note
    ): void {
        $stmt = $this->db->prepare(
            'UPDATE day_offs
             SET type = :type,
                 start_date = :start_date,
                 end_date = :end_date,
                 note = :note
             WHERE id = :id
               AND user_id = :user_id'
        );

        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'note' => $note !== '' ? $note : null,
        ]);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM day_offs
             WHERE id = :id
               AND user_id = :user_id'
        );

        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public function overlaps(
        int $userId,
        string $startDate,
        string $endDate,
        ?int $ignoreId = null
    ): bool {
        $sql = 'SELECT COUNT(*)
                FROM day_offs
                WHERE user_id = :user_id
                  AND start_date <= :end_date
                  AND end_date >= :start_date';

        $params = [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }
}
