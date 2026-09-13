<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSettings;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class PageController extends Controller
{
    public function history(): void
    {
        $this->requireAuth();

        $user = (new User())->find((int)authUser()['id']);
        $entryModel = new TimeEntry();
        $settings = (new WorkSettings())->all();
        $toleranceMinutes = (int)($settings['tolerance_minutes'] ?? 5);

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $entries = $entryModel->entriesBetween((int)$user['id'], $from, $to);

        $days = [];
        foreach ($entries as $entry) {
            $date = substr($entry['recorded_at'], 0, 10);
            $days[$date]['entries'][] = $entry;
        }

        foreach ($days as $date => &$day) {
            $day['summary'] = $entryModel->summarizeDay(
                (int)$user['id'],
                $date,
                (int)$user['daily_minutes'],
                $toleranceMinutes
            );
        }

        $this->view('history/index', [
            'title' => 'Histórico',
            'active' => 'history',
            'user' => $user,
            'days' => $days,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function calendar(): void
    {
        $this->requireAuth();

        $user = (new User())->find((int)authUser()['id']);
        $entryModel = new TimeEntry();
        $settings = (new WorkSettings())->all();
        $toleranceMinutes = (int)($settings['tolerance_minutes'] ?? 5);

        $month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? ''))
            ? (string)$_GET['month']
            : date('Y-m');

        $first = new DateTimeImmutable($month . '-01');
        $last = $first->modify('last day of this month');
        $calendar = [];

        for ($date = $first; $date <= $last; $date = $date->modify('+1 day')) {
            $calendar[] = [
                'date' => $date->format('Y-m-d'),
                'day' => (int)$date->format('j'),
                'weekday' => (int)$date->format('N'),
                'summary' => $entryModel->summarizeDay(
                    (int)$user['id'],
                    $date->format('Y-m-d'),
                    (int)$user['daily_minutes'],
                    $toleranceMinutes
                ),
            ];
        }

        $this->view('calendar/index', [
            'title' => 'Calendário',
            'active' => 'calendar',
            'user' => $user,
            'month' => $month,
            'firstWeekday' => (int)$first->format('N'),
            'calendar' => $calendar,
        ]);
    }

    public function profile(): void
    {
        $this->requireAuth();

        $this->view('profile/index', [
            'title' => 'Perfil',
            'active' => 'profile',
            'user' => (new User())->find((int)authUser()['id']),
            'success' => flash('success'),
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $salary = str_replace(',', '.', trim((string)($_POST['salary'] ?? '')));

        if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Dados inválidos.');
            redirect('profile');
        }

        (new User())->updateProfile((int)authUser()['id'], [
            'name' => $name,
            'email' => $email,
            'salary' => $salary,
        ]);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;

        flash('success', 'Perfil atualizado.');
        redirect('profile');
    }

    public function employees(): void
    {
        $this->requireAdmin();

        $this->view('employees/index', [
            'title' => 'Funcionários',
            'active' => 'employees',
            'user' => (new User())->find((int)authUser()['id']),
            'employees' => (new User())->all(),
        ]);
    }

    public function settings(): void
    {
        $this->requireAdmin();

        $this->view('settings/index', [
            'title' => 'Configurações',
            'active' => 'settings',
            'user' => (new User())->find((int)authUser()['id']),
            'settings' => (new WorkSettings())->all(),
            'success' => flash('success'),
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireAdmin();

        (new WorkSettings())->update([
            'workday_start' => (string)($_POST['workday_start'] ?? '08:00'),
            'workday_end' => (string)($_POST['workday_end'] ?? '17:48'),
            'lunch_minutes' => max(0, (int)($_POST['lunch_minutes'] ?? 60)),
            'overtime_weekday_percent' => max(0, (int)($_POST['overtime_weekday_percent'] ?? 50)),
            'overtime_saturday_percent' => max(0, (int)($_POST['overtime_saturday_percent'] ?? 100)),
            'overtime_sunday_percent' => max(0, (int)($_POST['overtime_sunday_percent'] ?? 100)),
            'tolerance_minutes' => max(0, (int)($_POST['tolerance_minutes'] ?? 5)),
        ]);

        flash('success', 'Configurações salvas.');
        redirect('settings');
    }
}
