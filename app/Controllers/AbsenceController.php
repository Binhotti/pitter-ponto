<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DayOff;
use App\Models\User;
use DateTimeImmutable;

class AbsenceController extends Controller
{
    private const TYPES = [
        'falta_justificada',
        'atestado',
        'folga',
        'ferias',
        'compensacao',
    ];

    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) authUser()['id'];
        $user = (new User())->find($userId);

        $this->view('absences/index', [
            'title' => 'Ausências e Justificativas',
            'active' => 'absences',
            'user' => $user,
            'items' => (new DayOff())->allForUser($userId),
            'success' => flash('success'),
            'error' => flash('error'),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();

        $userId = (int) authUser()['id'];
        $user = (new User())->find($userId);

        $type = trim((string) ($_POST['type'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));

        if (
            !in_array($type, self::TYPES, true)
            || !$this->validDate($startDate)
            || !$this->validDate($endDate)
        ) {
            flash('error', 'Preencha os dados da ocorrência corretamente.');
            redirect('absences');
        }

        if ($startDate > $endDate) {
            flash('error', 'A data final não pode ser anterior à data inicial.');
            redirect('absences');
        }

        $createdDate = substr((string) ($user['created_at'] ?? ''), 0, 10);

        if ($createdDate !== '' && $startDate < $createdDate) {
            flash(
                'error',
                'A ocorrência não pode começar antes da criação da sua conta.'
            );
            redirect('absences');
        }

        $model = new DayOff();

        if ($model->overlaps($userId, $startDate, $endDate)) {
            flash(
                'error',
                'Já existe uma ausência ou justificativa cadastrada nesse período.'
            );
            redirect('absences');
        }

        $model->create(
            $userId,
            $type,
            $startDate,
            $endDate,
            $note
        );

        flash('success', 'Ausência/justificativa adicionada com sucesso.');
        redirect('absences');
    }

    public function update(): void
    {
        $this->requireAuth();

        $userId = (int) authUser()['id'];
        $user = (new User())->find($userId);

        $id = (int) ($_POST['id'] ?? 0);
        $type = trim((string) ($_POST['type'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));

        $model = new DayOff();
        $item = $model->findOwned($id, $userId);

        if (!$item) {
            flash('error', 'Registro não encontrado.');
            redirect('absences');
        }

        if (
            !in_array($type, self::TYPES, true)
            || !$this->validDate($startDate)
            || !$this->validDate($endDate)
        ) {
            flash('error', 'Preencha os dados corretamente.');
            redirect('absences');
        }

        if ($startDate > $endDate) {
            flash('error', 'A data final não pode ser anterior à data inicial.');
            redirect('absences');
        }

        $createdDate = substr((string) ($user['created_at'] ?? ''), 0, 10);

        if ($createdDate !== '' && $startDate < $createdDate) {
            flash(
                'error',
                'A ocorrência não pode começar antes da criação da sua conta.'
            );
            redirect('absences');
        }

        if ($model->overlaps($userId, $startDate, $endDate, $id)) {
            flash(
                'error',
                'Esse período se sobrepõe a outra ocorrência cadastrada.'
            );
            redirect('absences');
        }

        $model->update(
            $id,
            $userId,
            $type,
            $startDate,
            $endDate,
            $note
        );

        flash('success', 'Registro atualizado com sucesso.');
        redirect('absences');
    }

    public function delete(): void
    {
        $this->requireAuth();

        $userId = (int) authUser()['id'];
        $id = (int) ($_POST['id'] ?? 0);

        $model = new DayOff();

        if (!$model->findOwned($id, $userId)) {
            flash('error', 'Registro não encontrado.');
            redirect('absences');
        }

        $model->delete($id, $userId);

        flash('success', 'Registro removido.');
        redirect('absences');
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date;
    }
}
