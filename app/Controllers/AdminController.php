<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DayOff;
use App\Models\Holiday;
use App\Models\LoginHistory;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSettings;
use App\Services\InconsistencyService;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use PDOException;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $this->requireAdmin();

        $userModel = new User();
        $entryModel = new TimeEntry();
        $historyModel = new LoginHistory();
        $dayOffModel = new DayOff();
        $holidayModel = new Holiday();
        $settings = (new WorkSettings())->all();

        $currentUser = $userModel->find((int)authUser()['id']);
        $users = $userModel->activeUsers();
        $today = date('Y-m-d');
        $tolerance = (int)($settings['tolerance_minutes'] ?? 5);

        $holidayModel->ensureYear((int)date('Y'));
        $todayHoliday = $holidayModel->findByDate($today);

        $team = [];
        $working = 0;
        $lunch = 0;
        $finished = 0;
        $notStarted = 0;
        $workedToday = 0;
        $extraToday = 0;

        foreach ($users as $user) {
            $status = $entryModel->currentStatus((int)$user['id']);
            $dayOff = $dayOffModel->findForDate((int)$user['id'], $today);

            $summary = $entryModel->summarizeDay(
                (int)$user['id'],
                $today,
                (int)($user['daily_minutes'] ?? 528),
                $tolerance,
                $todayHoliday,
                $dayOff,
                $user['created_at'] ?? null
            );

            match ($status['key']) {
                'working' => $working++,
                'lunch' => $lunch++,
                'finished' => $finished++,
                default => $notStarted++,
            };

            $workedToday += $summary['worked'];
            $extraToday += $summary['overtime50'] + $summary['overtime100'];

            $entries = $entryModel->entriesForDate((int)$user['id'], $today);
            $clockIn = null;

            foreach ($entries as $entry) {
                if ($entry['entry_type'] === 'clock_in') {
                    $clockIn = date('H:i', strtotime($entry['recorded_at']));
                    break;
                }
            }

            $team[] = [
                'user' => $user,
                'status' => $status,
                'summary' => $summary,
                'clockIn' => $clockIn,
                'dayOff' => $dayOff,
                'lastLogin' => $historyModel->lastForUser((int)$user['id']),
            ];
        }

        usort($team, function (array $a, array $b): int {
            $priority = [
                'working' => 1,
                'lunch' => 2,
                'finished' => 3,
                'not_started' => 4,
            ];

            $aPriority = $priority[$a['status']['key']] ?? 9;
            $bPriority = $priority[$b['status']['key']] ?? 9;

            return $aPriority === $bPriority
                ? strcmp($a['user']['name'], $b['user']['name'])
                : $aPriority <=> $bPriority;
        });

        $issues = [];

        foreach ($users as $user) {
            foreach ((new InconsistencyService())->forUser($user, 14) as $issue) {
                $issue['user_id'] = (int)$user['id'];
                $issue['user_name'] = $user['name'];
                $issues[] = $issue;
            }
        }

        usort(
            $issues,
            fn(array $a, array $b): int => strcmp($b['date'], $a['date'])
        );

        $this->view('admin/dashboard', [
            'title' => 'Administração',
            'active' => 'admin',
            'user' => $currentUser,
            'totalUsers' => $userModel->countAll(),
            'activeUsers' => $userModel->countActive(),
            'working' => $working,
            'lunch' => $lunch,
            'finished' => $finished,
            'notStarted' => $notStarted,
            'workedToday' => $workedToday,
            'extraToday' => $extraToday,
            'loginsToday' => $historyModel->countToday(),
            'uniqueLoginsToday' => $historyModel->uniqueUsersToday(),
            'loginChart' => $this->loginChart($historyModel->dailyCounts(7)),
            'recentLogins' => $historyModel->recent(8),
            'team' => $team,
            'todayDayOffs' => $dayOffModel->allForDate($today),
            'upcomingDayOffs' => $dayOffModel->upcoming(6),
            'issues' => array_slice($issues, 0, 8),
        ]);
    }

    public function users(): void
    {
        $this->requireAdmin();

        $userModel = new User();
        $entryModel = new TimeEntry();
        $historyModel = new LoginHistory();

        $currentUser = $userModel->find((int)authUser()['id']);
        $users = $userModel->all();
        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                'user' => $user,
                'status' => (int)$user['is_active'] === 1
                    ? $entryModel->currentStatus((int)$user['id'])
                    : [
                        'key' => 'inactive',
                        'label' => 'Conta inativa',
                    ],
                'workedToday' => $entryModel->workedMinutesForDate(
                    (int)$user['id'],
                    date('Y-m-d')
                ),
                'lastLogin' => $historyModel->lastForUser((int)$user['id']),
            ];
        }

        $this->view('admin/users', [
            'title' => 'Usuários',
            'active' => 'admin',
            'user' => $currentUser,
            'rows' => $rows,
        ]);
    }

    public function user(): void
    {
        $this->requireAdmin();

        $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$userId) {
            flash('error', 'Usuário inválido.');
            redirect('admin-users');
        }

        $userModel = new User();
        $entryModel = new TimeEntry();
        $historyModel = new LoginHistory();
        $dayOffModel = new DayOff();
        $holidayModel = new Holiday();
        $settings = (new WorkSettings())->all();

        $currentUser = $userModel->find((int)authUser()['id']);
        $selectedUser = $userModel->find((int)$userId);

        if (!$selectedUser) {
            flash('error', 'Usuário não encontrado.');
            redirect('admin-users');
        }

        $month = (string)($_GET['month'] ?? date('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $monthStart = new DateTimeImmutable($month . '-01');
        $monthEnd = $monthStart->modify('last day of this month');
        $holidayModel->ensureYear((int)$monthStart->format('Y'));

        $period = new DatePeriod(
            $monthStart,
            new DateInterval('P1D'),
            $monthEnd->modify('+1 day')
        );

        $tolerance = (int)($settings['tolerance_minutes'] ?? 5);
        $totals = [
            'worked' => 0,
            'extra50' => 0,
            'extra100' => 0,
            'bank' => 0,
            'absences' => 0,
            'completed' => 0,
        ];
        $days = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayOff = $dayOffModel->findForDate((int)$selectedUser['id'], $dateString);

            $summary = $entryModel->summarizeDay(
                (int)$selectedUser['id'],
                $dateString,
                (int)($selectedUser['daily_minutes'] ?? 528),
                $tolerance,
                $holidayModel->findByDate($dateString),
                $dayOff,
                $selectedUser['created_at'] ?? null
            );

            if (
                $summary['hasEntries']
                || $summary['absence']
                || $dayOff !== null
                || $summary['isHoliday']
            ) {
                $days[] = [
                    'date' => $dateString,
                    'summary' => $summary,
                    'dayOff' => $dayOff,
                    'entries' => $entryModel->entriesForDate(
                        (int)$selectedUser['id'],
                        $dateString
                    ),
                ];
            }

            if ($date <= new DateTimeImmutable('today')) {
                $totals['worked'] += $summary['worked'];
                $totals['extra50'] += $summary['overtime50'];
                $totals['extra100'] += $summary['overtime100'];
                $totals['bank'] += $summary['bankBalance'];
                $totals['absences'] += $summary['absence'] ? 1 : 0;
                $totals['completed'] += $summary['completed'] ? 1 : 0;
            }
        }

        $this->view('admin/user', [
            'title' => 'Detalhes do usuário',
            'active' => 'admin',
            'user' => $currentUser,
            'selectedUser' => $selectedUser,
            'status' => $entryModel->currentStatus((int)$selectedUser['id']),
            'lastLogin' => $historyModel->lastForUser((int)$selectedUser['id']),
            'recentLogins' => $historyModel->recentForUser((int)$selectedUser['id'], 8),
            'month' => $month,
            'totals' => $totals,
            'days' => array_reverse($days),
            'dayOffs' => $dayOffModel->allForUser((int)$selectedUser['id']),
            'issues' => (new InconsistencyService())->forUser($selectedUser, 30),
        ]);
    }

    public function editUser(): void
    {
        $this->requireAdmin();

        $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$userId) {
            flash('error', 'Usuário inválido.');
            redirect('admin-users');
        }

        $userModel = new User();
        $currentUser = $userModel->find((int)authUser()['id']);
        $selectedUser = $userModel->find((int)$userId);

        if (!$selectedUser) {
            flash('error', 'Usuário não encontrado.');
            redirect('admin-users');
        }

        $this->view('admin/edit-user', [
            'title' => 'Editar usuário',
            'active' => 'admin',
            'user' => $currentUser,
            'selectedUser' => $selectedUser,
            'error' => flash('error'),
            'success' => flash('success'),
            'warning' => flash('warning'),
        ]);
    }

    public function updateUser(): void
    {
        $this->requireAdmin();

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$userId) {
            flash('error', 'Usuário inválido.');
            redirect('admin-users');
        }

        $userModel = new User();
        $selectedUser = $userModel->find((int)$userId);

        if (!$selectedUser) {
            flash('error', 'Usuário não encontrado.');
            redirect('admin-users');
        }

        $name = preg_replace(
            '/\s+/u',
            ' ',
            trim((string)($_POST['name'] ?? ''))
        ) ?: '';

        $email = mb_strtolower(
            trim((string)($_POST['email'] ?? ''))
        );

        $role = (string)($_POST['role'] ?? 'employee');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = (string)($_POST['password'] ?? '');

        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            flash('error', 'Informe um nome válido entre 3 e 120 caracteres.');
            redirect('admin-user-edit&id=' . (int)$userId);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Informe um e-mail válido.');
            redirect('admin-user-edit&id=' . (int)$userId);
        }

        if (!in_array($role, ['admin', 'employee'], true)) {
            flash('error', 'Perfil de usuário inválido.');
            redirect('admin-user-edit&id=' . (int)$userId);
        }

        if ($userModel->emailExistsForOtherUser($email, (int)$userId)) {
            flash('error', 'Este e-mail já está sendo usado por outra conta.');
            redirect('admin-user-edit&id=' . (int)$userId);
        }

        if ($password !== '') {
            if (mb_strlen($password) < 8 || mb_strlen($password) > 128) {
                flash('error', 'A nova senha deve ter entre 8 e 128 caracteres.');
                redirect('admin-user-edit&id=' . (int)$userId);
            }

            if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
                flash('error', 'A nova senha precisa conter pelo menos uma letra e um número.');
                redirect('admin-user-edit&id=' . (int)$userId);
            }
        }

        /*
         * Evita que o último administrador ativo seja rebaixado
         * ou desativado sem querer.
         */
        $wouldRemoveAdmin =
            $selectedUser['role'] === 'admin'
            && (int)$selectedUser['is_active'] === 1
            && ($role !== 'admin' || $isActive !== 1);

        if ($wouldRemoveAdmin && $userModel->countAdmins() <= 1) {
            flash(
                'error',
                'Não é possível remover ou desativar o último administrador ativo.'
            );
            redirect('admin-user-edit&id=' . (int)$userId);
        }

        try {
            $userModel->updateByAdmin((int)$userId, [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'is_active' => $isActive,
                'password' => $password,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('error', 'Este e-mail já está sendo usado por outra conta.');
                redirect('admin-user-edit&id=' . (int)$userId);
            }

            throw $exception;
        }

        if ($userModel->nameExists($name, (int)$userId)) {
            flash(
                'warning',
                'Alterações salvas. Existe outra conta com o mesmo nome.'
            );
        } else {
            flash('success', 'Conta atualizada com sucesso.');
        }

        redirect('admin-user-edit&id=' . (int)$userId);
    }

    public function deleteUser(): void
    {
        $this->requireAdmin();

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$userId) {
            flash('error', 'Usuário inválido.');
            redirect('admin-users');
        }

        if ((int)$userId === (int)authUser()['id']) {
            flash(
                'error',
                'Você não pode excluir a própria conta enquanto está conectado.'
            );
            redirect('admin-users');
        }

        $userModel = new User();
        $selectedUser = $userModel->find((int)$userId);

        if (!$selectedUser) {
            flash('error', 'Usuário não encontrado.');
            redirect('admin-users');
        }

        if (
            $selectedUser['role'] === 'admin'
            && (int)$selectedUser['is_active'] === 1
            && $userModel->countAdmins() <= 1
        ) {
            flash(
                'error',
                'Não é possível excluir o último administrador ativo.'
            );
            redirect('admin-users');
        }

        try {
            $userModel->delete((int)$userId);
        } catch (PDOException $exception) {
            flash(
                'error',
                'Esta conta possui registros protegidos pelo histórico do sistema e não pôde ser excluída. Você pode desativá-la pela opção Editar.'
            );
            redirect('admin-users');
        }

        flash(
            'success',
            'Conta de ' . $selectedUser['name'] . ' excluída com sucesso.'
        );

        redirect('admin-users');
    }

    private function loginChart(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[$row['login_date']] = [
                'total' => (int)$row['total'],
                'unique' => (int)$row['unique_users'],
            ];
        }

        $chart = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = new DateTimeImmutable("today -{$i} days");
            $dateString = $date->format('Y-m-d');

            $chart[] = [
                'date' => $dateString,
                'label' => $date->format('d/m'),
                'day' => ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][(int)$date->format('w')],
                'total' => $map[$dateString]['total'] ?? 0,
                'unique' => $map[$dateString]['unique'] ?? 0,
            ];
        }

        return $chart;
    }
}
