<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DayOff;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\WorkSettings;
use DateTimeImmutable;

class NotificationService
{
    public function forUser(array $user): array
    {
        if ((int)($user['notifications_enabled'] ?? 1) !== 1) {
            return [];
        }

        $userId = (int)$user['id'];
        $today = date('Y-m-d');
        $now = new DateTimeImmutable('now');

        $entryModel = new TimeEntry();
        $holidayModel = new Holiday();
        $dayOffModel = new DayOff();
        $settings = (new WorkSettings())->all();

        $holidayModel->ensureYear((int)date('Y'));

        $todayHoliday = $holidayModel->findByDate($today);
        $todayDayOff = $dayOffModel->findForDate($userId, $today);
        $weekday = (int)(new DateTimeImmutable($today))->format('N');
        $isRequiredWorkday = $weekday <= 5
            && $todayHoliday === null
            && $todayDayOff === null;

        $status = $entryModel->currentStatus($userId);
        $entries = $entryModel->entriesForDate($userId, $today);

        $types = [];
        foreach ($entries as $entry) {
            $types[$entry['entry_type']] = $entry;
        }

        $tolerance = (int)($settings['tolerance_minutes'] ?? 5);
        $before = max(0, min(120, (int)($user['notification_before_minutes'] ?? 10)));
        $after = max(0, min(120, (int)($user['notification_after_minutes'] ?? 5)));

        $workdayStart = substr((string)($user['workday_start'] ?? '08:00:00'), 0, 5);
        $workdayEnd = substr((string)($user['workday_end'] ?? '17:48:00'), 0, 5);
        $lunchStart = substr((string)($user['lunch_start_time'] ?? '12:00:00'), 0, 5);
        $lunchMinutes = (int)($user['lunch_minutes'] ?? 60);

        $notifications = [];

        // Entrada: aviso antes e lembrete após o horário.
        if (
            $isRequiredWorkday
            && (int)($user['notify_entry_enabled'] ?? 1) === 1
            && !isset($types['clock_in'])
        ) {
            $target = new DateTimeImmutable($today . ' ' . $workdayStart);

            if ($now >= $target->modify("-{$before} minutes") && $now < $target) {
                $notifications[] = $this->notification(
                    'entry-upcoming-' . $today,
                    'info',
                    'clock-3',
                    'Entrada se aproximando',
                    "Seu expediente começa às {$workdayStart}.",
                    'dashboard'
                );
            } elseif ($now > $target->modify("+{$after} minutes")) {
                $notifications[] = $this->notification(
                    'entry-late-' . $today,
                    'warning',
                    'log-in',
                    'Entrada ainda não registrada',
                    "Seu horário de entrada ({$workdayStart}) já passou.",
                    'dashboard'
                );
            }
        }

        // Início do almoço: só avisa se o usuário já entrou e ainda não iniciou o almoço.
        if (
            isset($types['clock_in'])
            && !isset($types['lunch_start'])
            && (int)($user['notify_lunch_start_enabled'] ?? 1) === 1
        ) {
            $target = new DateTimeImmutable($today . ' ' . $lunchStart);

            if ($now >= $target->modify("-{$before} minutes") && $now < $target) {
                $notifications[] = $this->notification(
                    'lunch-start-upcoming-' . $today,
                    'info',
                    'utensils',
                    'Horário de almoço se aproximando',
                    "Seu almoço está previsto para {$lunchStart}.",
                    'dashboard'
                );
            } elseif (
                $now > $target->modify("+{$after} minutes")
                && !isset($types['lunch_end'])
                && !isset($types['clock_out'])
            ) {
                $notifications[] = $this->notification(
                    'lunch-start-late-' . $today,
                    'warning',
                    'utensils',
                    'Início do almoço pendente',
                    "Seu horário de almoço ({$lunchStart}) já passou.",
                    'dashboard'
                );
            }
        }

        // Retorno do almoço: usa o horário REAL em que o almoço foi iniciado.
        if (
            isset($types['lunch_start'])
            && !isset($types['lunch_end'])
            && (int)($user['notify_lunch_return_enabled'] ?? 1) === 1
        ) {
            $startedAt = new DateTimeImmutable($types['lunch_start']['recorded_at']);
            $target = $startedAt->modify("+{$lunchMinutes} minutes");
            $targetLabel = $target->format('H:i');

            if ($now >= $target->modify("-{$before} minutes") && $now < $target) {
                $notifications[] = $this->notification(
                    'lunch-return-upcoming-' . $today,
                    'info',
                    'coffee',
                    'Volta do almoço se aproximando',
                    "Seu retorno está previsto para {$targetLabel}.",
                    'dashboard'
                );
            } elseif ($now > $target->modify("+{$after} minutes")) {
                $notifications[] = $this->notification(
                    'lunch-return-late-' . $today,
                    'warning',
                    'coffee',
                    'Volta do almoço pendente',
                    "Seu intervalo terminou por volta de {$targetLabel}.",
                    'dashboard'
                );
            }
        }

        // Saída: só avisa se houve entrada e o expediente ainda não foi finalizado.
        if (
            isset($types['clock_in'])
            && !isset($types['clock_out'])
            && (int)($user['notify_clock_out_enabled'] ?? 1) === 1
        ) {
            $target = new DateTimeImmutable($today . ' ' . $workdayEnd);

            if ($now >= $target->modify("-{$before} minutes") && $now < $target) {
                $notifications[] = $this->notification(
                    'clock-out-upcoming-' . $today,
                    'info',
                    'log-out',
                    'Fim do expediente se aproximando',
                    "Sua saída padrão é às {$workdayEnd}.",
                    'dashboard'
                );
            } elseif ($now > $target->modify("+{$after} minutes")) {
                $notifications[] = $this->notification(
                    'clock-out-late-' . $today,
                    'danger',
                    'log-out',
                    'Saída ainda não registrada',
                    "Seu horário de saída ({$workdayEnd}) já passou.",
                    'dashboard'
                );
            }
        }

        return $notifications;
    }

    private function notification(
        string $key,
        string $type,
        string $icon,
        string $title,
        string $message,
        string $route
    ): array {
        return [
            'key' => $key,
            'type' => $type,
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
            'action' => url($route) . '#meu-ponto',
        ];
    }
}
