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

        /*
         * Se o expediente já foi finalizado, não há mais nenhuma
         * notificação operacional a gerar naquele dia.
         *
         * Isso evita avisos incorretos como:
         * - lembrar de iniciar o almoço após a pessoa já ter saído;
         * - lembrar de voltar do almoço após o expediente ter acabado;
         * - lembrar da saída depois do clock_out.
         */
        if ($status['key'] === 'finished') {
            return [];
        }

        $types = [];
        foreach ($entries as $entry) {
            $types[$entry['entry_type']] = $entry;
        }

        $tolerance = (int)($settings['tolerance_minutes'] ?? 5);
        $before = max(0, min(120, (int)($user['notification_before_minutes'] ?? 10)));
        $after = max(0, min(120, (int)($user['notification_after_minutes'] ?? 5)));

        $workdayStart = substr((string)($user['workday_start'] ?? '08:00:00'), 0, 5);
        $workdayEnd = substr((string)($user['workday_end'] ?? '17:48:00'), 0, 5);
        $learnedLunch = $entryModel->averageLunchStartTime($userId, 10, 3);
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

        /*
         * Início do almoço:
         * o sistema aprende o horário com o histórico do próprio usuário.
         * Só começa a avisar depois de pelo menos 3 dias úteis com almoço
         * registrado, evitando inventar um horário padrão.
         */
        if (
            isset($types['clock_in'])
            && !isset($types['lunch_start'])
            && !isset($types['clock_out'])
            && (int)($user['notify_lunch_start_enabled'] ?? 1) === 1
            && $learnedLunch !== null
        ) {
            $learnedLunchTime = $learnedLunch['time'];
            $target = new DateTimeImmutable($today . ' ' . $learnedLunchTime);

            if ($now >= $target->modify("-{$before} minutes") && $now < $target) {
                $notifications[] = $this->notification(
                    'lunch-start-upcoming-' . $today,
                    'info',
                    'utensils',
                    'Seu horário habitual de almoço está chegando',
                    "Pelo seu histórico, você costuma almoçar por volta de {$learnedLunchTime}.",
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
                    'Almoço ainda não registrado',
                    "Pelo seu histórico, você costuma iniciar o almoço por volta de {$learnedLunchTime}.",
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
            'action' => url($route),
        ];
    }
}
