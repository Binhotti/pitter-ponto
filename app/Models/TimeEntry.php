<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use PDO;

class TimeEntry
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function create(int $userId, string $type): string
    {
        /*
         * Usa o horário da aplicação (America/Sao_Paulo por padrão)
         * em vez de NOW() do MySQL. Isso evita diferença de fuso entre
         * XAMPP e hospedagens como o InfinityFree.
         */
        $recordedAt = (new DateTimeImmutable('now'))
            ->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO time_entries (
                user_id,
                entry_type,
                recorded_at,
                source
             )
             VALUES (
                :user_id,
                :entry_type,
                :recorded_at,
                "web"
             )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'entry_type' => $type,
            'recorded_at' => $recordedAt,
        ]);

        return $recordedAt;
    }

    public function entriesForDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM time_entries
             WHERE user_id = :user_id
               AND DATE(recorded_at) = :date
             ORDER BY recorded_at ASC'
        );

        $stmt->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);

        return $stmt->fetchAll();
    }

    public function recent(int $userId, int $limit = 7): array
    {
        $limit = max(1, min($limit, 30));

        $stmt = $this->db->prepare(
            "SELECT * FROM time_entries
             WHERE user_id = :user_id
             ORDER BY recorded_at DESC
             LIMIT {$limit}"
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function entriesBetween(int $userId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM time_entries
             WHERE user_id = :user_id
               AND DATE(recorded_at) BETWEEN :from_date AND :to_date
             ORDER BY recorded_at ASC'
        );

        $stmt->execute([
            'user_id' => $userId,
            'from_date' => $from,
            'to_date' => $to,
        ]);

        return $stmt->fetchAll();
    }

    public function searchForUser(
        int $userId,
        string $query,
        int $limit = 20
    ): array {
        $limit = max(1, min($limit, 50));

        $typeMap = [
            'entrada' => 'clock_in',
            'almoço' => 'lunch_start',
            'almoco' => 'lunch_start',
            'início do almoço' => 'lunch_start',
            'inicio do almoco' => 'lunch_start',
            'volta do almoço' => 'lunch_end',
            'volta do almoco' => 'lunch_end',
            'retorno' => 'lunch_end',
            'saída' => 'clock_out',
            'saida' => 'clock_out',
        ];

        $normalized = mb_strtolower(trim($query));
        $type = $typeMap[$normalized] ?? null;

        if ($type !== null) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM time_entries
                 WHERE user_id = :user_id
                   AND entry_type = :entry_type
                 ORDER BY recorded_at DESC
                 LIMIT {$limit}"
            );

            $stmt->execute([
                'user_id' => $userId,
                'entry_type' => $type,
            ]);

            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare(
            "SELECT *
             FROM time_entries
             WHERE user_id = :user_id
               AND (
                    note LIKE :query_note
                    OR source LIKE :query_source
               )
             ORDER BY recorded_at DESC
             LIMIT {$limit}"
        );

        $search = '%' . $query . '%';

        $stmt->execute([
            'user_id' => $userId,
            'query_note' => $search,
            'query_source' => $search,
        ]);

        return $stmt->fetchAll();
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM time_entries
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function manualCreate(
        int $userId,
        string $type,
        string $recordedAt,
        string $reason
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO time_entries (
                user_id,
                entry_type,
                recorded_at,
                source,
                note
            )
            VALUES (
                :user_id,
                :entry_type,
                :recorded_at,
                "manual",
                :note
            )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'entry_type' => $type,
            'recorded_at' => $recordedAt,
            'note' => $reason,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateRecordedAt(
        int $entryId,
        int $userId,
        string $newRecordedAt,
        string $reason
    ): void {
        $entry = $this->findOwned($entryId, $userId);

        if (!$entry) {
            throw new \RuntimeException('Registro não encontrado.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'UPDATE time_entries
                 SET recorded_at = :recorded_at,
                     source = "manual",
                     note = :note
                 WHERE id = :id AND user_id = :user_id'
            );

            $stmt->execute([
                'recorded_at' => $newRecordedAt,
                'note' => $reason,
                'id' => $entryId,
                'user_id' => $userId,
            ]);

            $audit = $this->db->prepare(
                'INSERT INTO time_adjustments (
                    time_entry_id,
                    changed_by,
                    old_recorded_at,
                    new_recorded_at,
                    reason
                )
                VALUES (
                    :time_entry_id,
                    :changed_by,
                    :old_recorded_at,
                    :new_recorded_at,
                    :reason
                )'
            );

            $audit->execute([
                'time_entry_id' => $entryId,
                'changed_by' => $userId,
                'old_recorded_at' => $entry['recorded_at'],
                'new_recorded_at' => $newRecordedAt,
                'reason' => $reason,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function hasTypeForDate(
        int $userId,
        string $date,
        string $type,
        ?int $ignoreEntryId = null
    ): bool {
        $sql = 'SELECT COUNT(*)
                FROM time_entries
                WHERE user_id = :user_id
                  AND DATE(recorded_at) = :date
                  AND entry_type = :entry_type';

        $params = [
            'user_id' => $userId,
            'date' => $date,
            'entry_type' => $type,
        ];

        if ($ignoreEntryId !== null) {
            $sql .= ' AND id <> :ignore_entry_id';
            $params['ignore_entry_id'] = $ignoreEntryId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function validateDaySequence(
        int $userId,
        string $date,
        ?int $ignoreEntryId = null,
        ?array $replacement = null
    ): bool {
        $entries = $this->entriesForDate($userId, $date);

        $normalized = [];

        foreach ($entries as $entry) {
            if ($ignoreEntryId !== null && (int)$entry['id'] === $ignoreEntryId) {
                continue;
            }

            $normalized[] = [
                'type' => $entry['entry_type'],
                'recorded_at' => $entry['recorded_at'],
            ];
        }

        if ($replacement !== null) {
            $normalized[] = $replacement;
        }

        usort(
            $normalized,
            fn(array $a, array $b): int =>
                strcmp($a['recorded_at'], $b['recorded_at'])
        );

        $expected = [
            'clock_in',
            'lunch_start',
            'lunch_end',
            'clock_out',
        ];

        $seen = [];

        foreach ($normalized as $index => $entry) {
            $type = $entry['type'];

            if (in_array($type, $seen, true)) {
                return false;
            }

            $seen[] = $type;

            if (!isset($expected[$index]) || $expected[$index] !== $type) {
                return false;
            }
        }

        return true;
    }

    public function averageLunchStartTime(
        int $userId,
        int $limit = 10,
        int $minimumSamples = 3
    ): ?array {
        $limit = max(3, min($limit, 30));
        $minimumSamples = max(2, min($minimumSamples, $limit));

        /*
         * Usa somente dias úteis anteriores ao dia atual.
         * Também limita o horário entre 10:00 e 15:00 para evitar
         * registros claramente fora do padrão distorcendo a média.
         */
        $stmt = $this->db->prepare(
            "SELECT recorded_at
             FROM time_entries
             WHERE user_id = :user_id
               AND entry_type = 'lunch_start'
               AND DATE(recorded_at) < CURDATE()
               AND WEEKDAY(recorded_at) BETWEEN 0 AND 4
               AND TIME(recorded_at) BETWEEN '10:00:00' AND '15:00:00'
             ORDER BY recorded_at DESC
             LIMIT {$limit}"
        );

        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        if (count($rows) < $minimumSamples) {
            return null;
        }

        $minutes = [];

        foreach ($rows as $row) {
            $timestamp = strtotime((string)$row['recorded_at']);

            $minutes[] =
                ((int)date('H', $timestamp) * 60)
                + (int)date('i', $timestamp);
        }

        /*
         * Se já houver pelo menos 5 amostras, remove o menor e o maior
         * horário antes de calcular a média. Isso reduz o efeito de um
         * almoço excepcionalmente cedo/tarde sem abandonar a ideia de média.
         */
        sort($minutes);

        if (count($minutes) >= 5) {
            array_shift($minutes);
            array_pop($minutes);
        }

        $averageMinutes = (int)round(
            array_sum($minutes) / count($minutes)
        );

        $hours = intdiv($averageMinutes, 60);
        $mins = $averageMinutes % 60;

        return [
            'time' => sprintf('%02d:%02d', $hours, $mins),
            'minutes' => $averageMinutes,
            'samples' => count($rows),
            'used_samples' => count($minutes),
        ];
    }

    public function currentStatus(int $userId): array
    {
        $entries = $this->entriesForDate($userId, date('Y-m-d'));

        if ($entries === []) {
            return [
                'key' => 'not_started',
                'label' => 'Ponto ainda não iniciado',
                'next' => 'clock_in',
            ];
        }

        $last = end($entries);

        return match ($last['entry_type']) {
            'clock_in' => [
                'key' => 'working',
                'label' => 'Você está no trabalho',
                'next' => 'lunch_start',
            ],
            'lunch_start' => [
                'key' => 'lunch',
                'label' => 'Você está no horário de almoço',
                'next' => 'lunch_end',
            ],
            'lunch_end' => [
                'key' => 'working',
                'label' => 'Você está no trabalho',
                'next' => 'clock_out',
            ],
            'clock_out' => [
                'key' => 'finished',
                'label' => 'Expediente finalizado',
                'next' => null,
            ],
            default => [
                'key' => 'not_started',
                'label' => 'Ponto ainda não iniciado',
                'next' => 'clock_in',
            ],
        };
    }

    public function workedMinutesForDate(int $userId, string $date): int
    {
        $entries = $this->entriesForDate($userId, $date);
        $map = [];

        foreach ($entries as $entry) {
            $map[$entry['entry_type']] ??= $entry['recorded_at'];
        }

        $minutes = 0;

        if (isset($map['clock_in'], $map['lunch_start'])) {
            $minutes += $this->diffMinutes(
                $map['clock_in'],
                $map['lunch_start']
            );
        }

        if (isset($map['lunch_end'], $map['clock_out'])) {
            $minutes += $this->diffMinutes(
                $map['lunch_end'],
                $map['clock_out']
            );
        } elseif (isset($map['lunch_end']) && $date === date('Y-m-d')) {
            $minutes += $this->diffMinutes(
                $map['lunch_end'],
                date('Y-m-d H:i:s')
            );
        } elseif (
            isset($map['clock_in'])
            && !isset($map['lunch_start'])
            && $date === date('Y-m-d')
        ) {
            $minutes += $this->diffMinutes(
                $map['clock_in'],
                date('Y-m-d H:i:s')
            );
        }

        return max(0, $minutes);
    }

    public function summarizeDay(
        int $userId,
        string $date,
        int $dailyMinutes,
        int $toleranceMinutes = 5,
        ?array $holiday = null,
        ?array $dayOff = null,
        ?string $accountCreatedAt = null
    ): array {
        $entries = $this->entriesForDate($userId, $date);
        $worked = $this->workedMinutesForDate($userId, $date);

        $dateObject = new DateTimeImmutable($date);
        $weekday = (int) $dateObject->format('N');

        $isSaturday = $weekday === 6;
        $isSunday = $weekday === 7;
        $isWeekend = $isSaturday || $isSunday;
        $isHoliday = $holiday !== null;
        $isExcused = $dayOff !== null;

        $hasEntries = $entries !== [];
        $completed = false;

        foreach ($entries as $entry) {
            if ($entry['entry_type'] === 'clock_out') {
                $completed = true;
                break;
            }
        }

        $expected = (!$isWeekend && !$isHoliday && !$isExcused)
            ? $dailyMinutes
            : 0;

        $regular = $expected > 0
            ? min($worked, $dailyMinutes)
            : 0;

        $overtime65 = 0;
        $overtime100 = 0;
        $deficit = 0;
        $bankBalance = 0;
        $absence = false;

        $today = new DateTimeImmutable('today');
        $isPast = $dateObject < $today;

        /*
         * Ausência só pode existir depois da criação da conta.
         * O próprio dia do cadastro também não é marcado automaticamente
         * como ausência, pois o usuário pode ter criado a conta no meio
         * do expediente.
         */
        $accountCreatedDate = $accountCreatedAt
            ? (new DateTimeImmutable($accountCreatedAt))->setTime(0, 0)
            : null;

        $isAfterAccountCreation = $accountCreatedDate === null
            ? true
            : $dateObject > $accountCreatedDate;

        if (
            $isPast
            && $isAfterAccountCreation
            && !$hasEntries
            && $expected > 0
        ) {
            $absence = true;
            $deficit = $dailyMinutes;
            $bankBalance = -$dailyMinutes;
        }

        if ($completed) {
            /*
             * Sábado, domingo e feriado não fazem parte da jornada
             * obrigatória (segunda a sexta). Portanto, todo minuto
             * efetivamente trabalhado nesses dias é hora extra 100%.
             *
             * Ex.:
             * sábado 4h trabalhadas => 4h de extra 100%
             * sábado 9h09 => 9h09 de extra 100%
             */
            if ($isWeekend || $isHoliday) {
                $overtime100 = $worked;
                $bankBalance = $worked;
            } elseif ($isExcused) {
                $bankBalance = 0;
            } else {
                $difference = $worked - $expected;

                if (abs($difference) <= $toleranceMinutes) {
                    $difference = 0;
                }

                if ($difference > 0) {
                    $overtime65 = $difference;
                } elseif ($difference < 0) {
                    $deficit = abs($difference);
                }

                $bankBalance = $difference;
            }
        }

        return [
            'worked' => $worked,
            'expected' => $expected,
            'regular' => $regular,
            'overtime65' => $overtime65,
            'overtime100' => $overtime100,
            'deficit' => $deficit,
            'bankBalance' => $bankBalance,
            'hasEntries' => $hasEntries,
            'completed' => $completed,
            'absence' => $absence,
            'isSaturday' => $isSaturday,
            'isSunday' => $isSunday,
            'isHoliday' => $isHoliday,
            'holiday' => $holiday,
            'dayOff' => $dayOff,
        ];
    }

    private function diffMinutes(string $from, string $to): int
    {
        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);

        return (int) floor(
            ($end->getTimestamp() - $start->getTimestamp()) / 60
        );
    }
}
