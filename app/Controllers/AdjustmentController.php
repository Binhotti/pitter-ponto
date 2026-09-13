<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TimeEntry;
use DateTimeImmutable;

class AdjustmentController extends Controller
{
    private const TYPES = [
        'clock_in',
        'lunch_start',
        'lunch_end',
        'clock_out',
    ];

    public function update(): void
    {
        $this->requireAuth();

        $entryId = (int)($_POST['entry_id'] ?? 0);
        $date = trim((string)($_POST['date'] ?? ''));
        $time = trim((string)($_POST['time'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));

        if (
            $entryId <= 0
            || !$this->validDate($date)
            || !$this->validTime($time)
            || mb_strlen($reason) < 3
        ) {
            flash('error', 'Preencha horário e motivo da alteração corretamente.');
            redirect('history');
        }

        $userId = (int)authUser()['id'];
        $model = new TimeEntry();
        $entry = $model->findOwned($entryId, $userId);

        if (!$entry) {
            flash('error', 'Registro não encontrado.');
            redirect('history');
        }

        $originalDate = substr($entry['recorded_at'], 0, 10);

        if ($date !== $originalDate) {
            flash('error', 'Nesta versão, a edição mantém o registro no mesmo dia.');
            redirect('history');
        }

        $newRecordedAt = $date . ' ' . $time . ':00';

        if (
            !$model->validateDaySequence(
                $userId,
                $date,
                $entryId,
                [
                    'type' => $entry['entry_type'],
                    'recorded_at' => $newRecordedAt,
                ]
            )
        ) {
            flash(
                'error',
                'O novo horário quebraria a ordem do expediente. Confira entrada, almoço, retorno e saída.'
            );
            redirect('history');
        }

        try {
            $model->updateRecordedAt(
                $entryId,
                $userId,
                $newRecordedAt,
                $reason
            );

            flash('success', 'Registro alterado com sucesso.');
        } catch (\Throwable $exception) {
            flash('error', 'Não foi possível alterar o registro.');
        }

        redirect('history');
    }

    public function create(): void
    {
        $this->requireAuth();

        $date = trim((string)($_POST['date'] ?? ''));
        $time = trim((string)($_POST['time'] ?? ''));
        $type = trim((string)($_POST['type'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));

        if (
            !$this->validDate($date)
            || !$this->validTime($time)
            || !in_array($type, self::TYPES, true)
            || mb_strlen($reason) < 3
        ) {
            flash('error', 'Preencha todos os dados do ponto esquecido.');
            redirect('history');
        }

        $userId = (int)authUser()['id'];
        $model = new TimeEntry();

        if ($model->hasTypeForDate($userId, $date, $type)) {
            flash('error', 'Esse tipo de ponto já existe neste dia.');
            redirect('history');
        }

        $recordedAt = $date . ' ' . $time . ':00';

        if (
            !$model->validateDaySequence(
                $userId,
                $date,
                null,
                [
                    'type' => $type,
                    'recorded_at' => $recordedAt,
                ]
            )
        ) {
            flash(
                'error',
                'O ponto não pode ser adicionado nessa posição. Respeite a ordem: entrada, início do almoço, volta do almoço e saída.'
            );
            redirect('history');
        }

        $model->manualCreate(
            $userId,
            $type,
            $recordedAt,
            $reason
        );

        flash('success', 'Ponto esquecido adicionado com sucesso.');
        redirect('history');
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date;
    }

    private function validTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }
}
