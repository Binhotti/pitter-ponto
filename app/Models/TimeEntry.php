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

    public function create(int $userId, string $type): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO time_entries (user_id, entry_type, recorded_at, source)
             VALUES (:user_id, :entry_type, NOW(), "web")'
        );

        $stmt->execute([
            'user_id' => $userId,
            'entry_type' => $type,
        ]);
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

    public function currentStatus(int $userId): array
    {
        $today = date('Y-m-d');
        $entries = $this->entriesForDate($userId, $today);

        if ($entries === []) {
            return [
                'key' => 'not_started',
                'label' => 'Ponto ainda não iniciado',
                'next' => 'clock_in',
            ];
        }

        $last = end($entries);
        $type = $last['entry_type'];

        return match ($type) {
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
        } elseif (
            isset($map['lunch_end'])
            && $date === date('Y-m-d')
        ) {
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
        int $toleranceMinutes = 5
    ): array {
        $entries = $this->entriesForDate($userId, $date);

        $worked = $this->workedMinutesForDate($userId, $date);

        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        $isSaturday = $weekday === 6;
        $isSunday = $weekday === 7;
        $isWeekend = $isSaturday || $isSunday;

        $hasEntries = $entries !== [];
        $completed = false;

        foreach ($entries as $entry) {
            if ($entry['entry_type'] === 'clock_out') {
                $completed = true;
                break;
            }
        }

        $expected = $isWeekend ? 0 : $dailyMinutes;

        $regular = $isWeekend
            ? 0
            : min($worked, $dailyMinutes);

        $overtime50 = 0;
        $overtime100 = 0;
        $deficit = 0;
        $bankBalance = 0;

        /*
         * O banco de horas só é consolidado quando o expediente foi finalizado.
         * Assim, enquanto o usuário ainda está trabalhando, o painel não cria
         * um saldo negativo temporário.
         */
        if ($completed) {
            if ($isWeekend) {
                // Sábado e domingo: toda hora trabalhada é extra 100%
                // e também entra positivamente no banco de horas.
                $overtime100 = $worked;
                $bankBalance = $worked;
            } else {
                $difference = $worked - $expected;

                // Diferenças dentro da tolerância são desconsideradas.
                if (abs($difference) <= $toleranceMinutes) {
                    $difference = 0;
                }

                if ($difference > 0) {
                    $overtime50 = $difference;
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
            'overtime50' => $overtime50,
            'overtime100' => $overtime100,
            'deficit' => $deficit,
            'bankBalance' => $bankBalance,
            'hasEntries' => $hasEntries,
            'completed' => $completed,
            'isSaturday' => $isSaturday,
            'isSunday' => $isSunday,
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
