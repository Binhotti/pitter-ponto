<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DayOff;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\WorkSettings;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class InconsistencyService
{
    public function forUser(array $user, int $daysBack = 30): array
    {
        $userId = (int)$user['id'];
        $dailyMinutes = (int)($user['daily_minutes'] ?? 528);
        $settings = (new WorkSettings())->all();
        $tolerance = (int)($settings['tolerance_minutes'] ?? 5);

        $entryModel = new TimeEntry();
        $holidayModel = new Holiday();
        $dayOffModel = new DayOff();

        $today = new DateTimeImmutable('today');
        $end = $today->modify('-1 day');
        $start = $today->modify('-' . max(1, $daysBack) . ' days');

        if (!empty($user['created_at'])) {
            $created = (new DateTimeImmutable((string)$user['created_at']))
                ->setTime(0, 0)
                ->modify('+1 day');

            if ($created > $start) {
                $start = $created;
            }
        }

        if ($start > $end) {
            return [];
        }

        $holidayModel->ensureYear((int)$start->format('Y'));
        if ($start->format('Y') !== $end->format('Y')) {
            $holidayModel->ensureYear((int)$end->format('Y'));
        }

        $issues = [];
        $period = new DatePeriod(
            $start,
            new DateInterval('P1D'),
            $end->modify('+1 day')
        );

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $holiday = $holidayModel->findByDate($dateString);
            $dayOff = $dayOffModel->findForDate($userId, $dateString);
            $entries = $entryModel->entriesForDate($userId, $dateString);

            $summary = $entryModel->summarizeDay(
                $userId,
                $dateString,
                $dailyMinutes,
                $tolerance,
                $holiday,
                $dayOff,
                $user['created_at'] ?? null
            );

            if ($summary['absence']) {
                $issues[] = $this->issue(
                    $dateString,
                    'missing_day',
                    'danger',
                    'calendar-x-2',
                    'Dia útil sem ponto registrado',
                    'Não há marcações nem justificativa para este dia.',
                    url('absences')
                );
                continue;
            }

            if ($entries === []) {
                continue;
            }

            $types = array_column($entries, 'entry_type');
            $expectedSequence = [
                'clock_in',
                'lunch_start',
                'lunch_end',
                'clock_out',
            ];

            // Detecta tipos duplicados.
            if (count($types) !== count(array_unique($types))) {
                $issues[] = $this->issue(
                    $dateString,
                    'duplicate',
                    'danger',
                    'copy-x',
                    'Marcações duplicadas',
                    'Há mais de uma marcação do mesmo tipo neste dia.',
                    url('history') . '&from=' . $dateString . '&to=' . $dateString
                );
                continue;
            }

            // Detecta sequência incompleta ou inválida.
            $validPrefix = true;
            foreach ($types as $index => $type) {
                if (!isset($expectedSequence[$index]) || $type !== $expectedSequence[$index]) {
                    $validPrefix = false;
                    break;
                }
            }

            if (!$validPrefix) {
                $issues[] = $this->issue(
                    $dateString,
                    'invalid_sequence',
                    'danger',
                    'triangle-alert',
                    'Sequência de ponto inconsistente',
                    'A ordem das marcações não segue entrada → almoço → retorno → saída.',
                    url('history') . '&from=' . $dateString . '&to=' . $dateString
                );
                continue;
            }

            if (!$summary['completed']) {
                $nextLabels = [
                    1 => 'início do almoço',
                    2 => 'volta do almoço',
                    3 => 'saída',
                ];

                $missing = $nextLabels[count($types)] ?? 'próxima marcação';

                $issues[] = $this->issue(
                    $dateString,
                    'incomplete',
                    'warning',
                    'clock-alert',
                    'Expediente incompleto',
                    'Ficou faltando registrar ' . $missing . '.',
                    url('history') . '&from=' . $dateString . '&to=' . $dateString
                );
                continue;
            }

            // Jornada extrema: ajuda a detectar horários digitados incorretamente.
            if ($summary['worked'] > 16 * 60) {
                $issues[] = $this->issue(
                    $dateString,
                    'long_shift',
                    'warning',
                    'timer-off',
                    'Jornada muito longa',
                    'Foram contabilizadas mais de 16 horas neste dia. Vale conferir as marcações.',
                    url('history') . '&from=' . $dateString . '&to=' . $dateString
                );
            }

            // Intervalo muito longo (mais de 3h) costuma indicar erro de marcação.
            $byType = [];
            foreach ($entries as $entry) {
                $byType[$entry['entry_type']] = $entry;
            }

            if (isset($byType['lunch_start'], $byType['lunch_end'])) {
                $startLunch = new DateTimeImmutable($byType['lunch_start']['recorded_at']);
                $endLunch = new DateTimeImmutable($byType['lunch_end']['recorded_at']);
                $lunchMinutes = (int)(($endLunch->getTimestamp() - $startLunch->getTimestamp()) / 60);

                if ($lunchMinutes > 180) {
                    $issues[] = $this->issue(
                        $dateString,
                        'long_lunch',
                        'warning',
                        'coffee',
                        'Intervalo de almoço fora do padrão',
                        'O intervalo registrado passou de 3 horas.',
                        url('history') . '&from=' . $dateString . '&to=' . $dateString
                    );
                }
            }
        }

        usort(
            $issues,
            fn(array $a, array $b): int =>
                strcmp($b['date'], $a['date'])
        );

        return $issues;
    }

    private function issue(
        string $date,
        string $key,
        string $severity,
        string $icon,
        string $title,
        string $message,
        string $action
    ): array {
        return [
            'date' => $date,
            'key' => $key . '-' . $date,
            'severity' => $severity,
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
            'action' => $action,
        ];
    }
}
