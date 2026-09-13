<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TimeEntry;
use App\Models\Holiday;
use App\Models\DayOff;
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
        $holidayModel = new Holiday();
        $dayOffModel = new DayOff();
        $toleranceMinutes = (int)($settings['tolerance_minutes'] ?? 5);

        $today = new DateTimeImmutable('today');
        $allowedRanges = [1, 3, 5, 7];

        $rangeDays = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT);

        if ($rangeDays !== false && $rangeDays !== null && in_array($rangeDays, $allowedRanges, true)) {
            $toDate = $today;
            $fromDate = $today->modify('-' . ($rangeDays - 1) . ' days');
        } else {
            $rawFrom = (string)($_GET['from'] ?? '');
            $rawTo = (string)($_GET['to'] ?? '');

            $fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $rawFrom) ?: null;
            $toDate = DateTimeImmutable::createFromFormat('!Y-m-d', $rawTo) ?: null;

            if (!$fromDate || !$toDate) {
                $rangeDays = 7;
                $toDate = $today;
                $fromDate = $today->modify('-6 days');
            } else {
                if ($toDate > $today) {
                    $toDate = $today;
                }

                if ($fromDate > $toDate) {
                    [$fromDate, $toDate] = [$toDate, $fromDate];
                }

                // O filtro visual foi pensado para no máximo 7 dias.
                $minimumFrom = $toDate->modify('-6 days');
                if ($fromDate < $minimumFrom) {
                    $fromDate = $minimumFrom;
                }

                $rangeDays = null;
            }
        }

        $from = $fromDate->format('Y-m-d');
        $to = $toDate->format('Y-m-d');

        $holidayModel->ensureYear((int)$fromDate->format('Y'));
        if ($fromDate->format('Y') !== $toDate->format('Y')) {
            $holidayModel->ensureYear((int)$toDate->format('Y'));
        }

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
                $toleranceMinutes,
                $holidayModel->findByDate($date),
                $dayOffModel->findForDate((int)$user['id'], $date),
                $user['created_at'] ?? null
            );
        }
        unset($day);

        $this->view('history/index', [
            'title' => 'Histórico',
            'active' => 'history',
            'user' => $user,
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'rangeDays' => $rangeDays,
            'success' => flash('success'),
            'error' => flash('error'),
        ]);
    }

    public function calendar(): void
    {
        $this->requireAuth();

        $user = (new User())->find((int)authUser()['id']);
        $entryModel = new TimeEntry();
        $settings = (new WorkSettings())->all();
        $holidayModel = new Holiday();
        $dayOffModel = new DayOff();

        $toleranceMinutes = (int)($settings['tolerance_minutes'] ?? 5);

        $month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? ''))
            ? (string)$_GET['month']
            : date('Y-m');

        $first = new DateTimeImmutable($month . '-01');
        $last = $first->modify('last day of this month');

        $holidayModel->ensureYear((int)$first->format('Y'));

        $calendarHourlyValue = null;
        $calendarSalary = (float)($user['salary'] ?? 0);
        $calendarMonthlyHours = (int)($user['monthly_hours'] ?? 0);

        if ($calendarSalary > 0 && $calendarMonthlyHours > 0) {
            $calendarHourlyValue = $calendarSalary / $calendarMonthlyHours;
        }

        $calendar = [];

        for ($date = $first; $date <= $last; $date = $date->modify('+1 day')) {
            $dateString = $date->format('Y-m-d');
            $holiday = $holidayModel->findByDate($dateString);
            $dayOff = $dayOffModel->findForDate((int)$user['id'], $dateString);

            $summary = $entryModel->summarizeDay(
                (int)$user['id'],
                $dateString,
                (int)$user['daily_minutes'],
                $toleranceMinutes,
                $holiday,
                $dayOff,
                $user['created_at'] ?? null
            );

            $entries = $entryModel->entriesForDate(
                (int)$user['id'],
                $dateString
            );

            $estimatedExtraValue = null;

            if ($calendarHourlyValue !== null) {
                $estimatedExtraValue =
                    ($summary['overtime50'] / 60) * $calendarHourlyValue * 1.5
                    + ($summary['overtime100'] / 60) * $calendarHourlyValue * 2;
            }

            $calendar[] = [
                'date' => $dateString,
                'day' => (int)$date->format('j'),
                'weekday' => (int)$date->format('N'),
                'holiday' => $holiday,
                'dayOff' => $dayOff,
                'entries' => $entries,
                'estimatedExtraValue' => $estimatedExtraValue,
                'summary' => $summary,
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

        $userId = (int) authUser()['id'];
        $userModel = new User();
        $current = $userModel->find($userId);

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $salary = str_replace(',', '.', trim((string)($_POST['salary'] ?? '')));

        if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Dados inválidos.');
            redirect('profile');
        }

        $emailDomain = substr(
            $email,
            (int)strrpos($email, '@') + 1
        );

        if (
            $emailDomain === ''
            || !(
                checkdnsrr($emailDomain, 'MX')
                || checkdnsrr($emailDomain, 'A')
                || checkdnsrr($emailDomain, 'AAAA')
            )
        ) {
            flash('error', 'O domínio deste e-mail não pôde ser validado.');
            redirect('profile');
        }

        if ($userModel->emailExistsForOtherUser($email, $userId)) {
            flash('error', 'Este e-mail já está sendo usado por outra conta.');
            redirect('profile');
        }

        $duplicateName = $userModel->nameExists($name, $userId);

        try {
            $userModel->updateProfile($userId, [
            'name' => $name,
            'email' => $email,
            'salary' => $salary,
            ]);
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('error', 'Este e-mail já está sendo usado por outra conta.');
                redirect('profile');
            }

            throw $exception;
        }

        if ($duplicateName) {
            flash(
                'warning',
                'Aviso: já existe outra pessoa cadastrada com esse mesmo nome.'
            );
        }

        if (
            isset($_FILES['avatar'])
            && is_array($_FILES['avatar'])
            && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ) {
            $this->handleAvatarUpload($userId, $userModel);
        }

        if (isset($_POST['remove_avatar'])) {
            $this->removeAvatar(
                $userId,
                $userModel,
                $current['avatar_path'] ?? null
            );
        }

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
        $this->requireAuth();

        $this->view('settings/index', [
            'title' => 'Configurações',
            'active' => 'settings',
            'user' => (new User())->find((int) authUser()['id']),
            'success' => flash('success'),
            'error' => flash('error'),
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireAuth();

        $userId = (int) authUser()['id'];
        $userModel = new User();
        $user = $userModel->find($userId);

        $workdayStart = trim((string) ($_POST['workday_start'] ?? '08:00'));
        $workdayEnd = trim((string) ($_POST['workday_end'] ?? '17:48'));
        $lunchMinutes = max(0, min(240, (int) ($_POST['lunch_minutes'] ?? 60)));
        $theme = (string) ($_POST['theme'] ?? 'light');
        $notificationsEnabled = isset($_POST['notifications_enabled']) ? 1 : 0;
        $browserNotifications = isset($_POST['browser_notifications']) ? 1 : 0;
        $notificationBeforeMinutes = max(0, min(120, (int)($_POST['notification_before_minutes'] ?? 10)));
        $notificationAfterMinutes = max(0, min(120, (int)($_POST['notification_after_minutes'] ?? 5)));
        $notifyEntryEnabled = isset($_POST['notify_entry_enabled']) ? 1 : 0;
        $notifyLunchStartEnabled = isset($_POST['notify_lunch_start_enabled']) ? 1 : 0;
        $notifyLunchReturnEnabled = isset($_POST['notify_lunch_return_enabled']) ? 1 : 0;
        $notifyClockOutEnabled = isset($_POST['notify_clock_out_enabled']) ? 1 : 0;

        if (
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $workdayStart)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $workdayEnd)
        ) {
            flash('error', 'Informe horários válidos.');
            redirect('settings');
        }

        if (!in_array($theme, ['light', 'dark'], true)) {
            $theme = 'light';
        }

        $start = strtotime('1970-01-01 ' . $workdayStart . ':00');
        $end = strtotime('1970-01-01 ' . $workdayEnd . ':00');

        if ($end <= $start) {
            flash('error', 'O horário de saída precisa ser posterior ao horário de entrada.');
            redirect('settings');
        }

        $totalMinutes = (int) floor(($end - $start) / 60);
        $dailyMinutes = $totalMinutes - $lunchMinutes;

        if ($dailyMinutes <= 0) {
            flash('error', 'A duração do almoço não pode consumir toda a jornada.');
            redirect('settings');
        }

        $userModel->updatePersonalSettings($userId, [
            'workday_start' => $workdayStart . ':00',
            'workday_end' => $workdayEnd . ':00',
            'lunch_minutes' => $lunchMinutes,
            'daily_minutes' => $dailyMinutes,
            'theme' => $theme,
            'notifications_enabled' => $notificationsEnabled,
            'browser_notifications' => $browserNotifications,
            'notification_before_minutes' => $notificationBeforeMinutes,
            'notification_after_minutes' => $notificationAfterMinutes,
            'notify_entry_enabled' => $notifyEntryEnabled,
            'notify_lunch_start_enabled' => $notifyLunchStartEnabled,
            'notify_lunch_return_enabled' => $notifyLunchReturnEnabled,
            'notify_clock_out_enabled' => $notifyClockOutEnabled,
        ]);

        $name = trim((string) ($_POST['name'] ?? $user['name']));
        $email = strtolower(trim((string) ($_POST['email'] ?? $user['email'])));

        if (mb_strlen($name) >= 3 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $userModel->updateProfile($userId, [
                'name' => $name,
                'email' => $email,
                'salary' => (string) ($user['salary'] ?? ''),
            ]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
        }

        if (
            isset($_FILES['avatar'])
            && is_array($_FILES['avatar'])
            && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ) {
            $this->handleAvatarUpload($userId, $userModel);
        }

        if (isset($_POST['remove_avatar'])) {
            $this->removeAvatar($userId, $userModel, $user['avatar_path'] ?? null);
        }

        flash('success', 'Configurações atualizadas com sucesso.');
        redirect('settings');
    }

    private function handleAvatarUpload(int $userId, User $userModel): void
    {
        $file = $_FILES['avatar'];

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            flash('error', 'Não foi possível enviar a foto de perfil.');
            return;
        }

        if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
            flash('error', 'A foto de perfil deve ter no máximo 3 MB.');
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = mime_content_type($tmp);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            flash('error', 'Use uma imagem JPG, PNG ou WEBP.');
            return;
        }

        $directory = BASE_PATH . '/public/assets/uploads/avatars';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = 'user-' . $userId . '-' . bin2hex(random_bytes(6))
            . '.' . $extensions[$mime];

        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmp, $destination)) {
            flash('error', 'Não foi possível salvar a foto de perfil.');
            return;
        }

        $current = $userModel->find($userId);

        if (!empty($current['avatar_path'])) {
            $old = BASE_PATH . '/public/' . ltrim((string) $current['avatar_path'], '/');

            if (is_file($old)) {
                @unlink($old);
            }
        }

        $userModel->updateAvatar(
            $userId,
            'assets/uploads/avatars/' . $filename
        );
    }

    private function removeAvatar(
        int $userId,
        User $userModel,
        ?string $avatarPath
    ): void {
        if ($avatarPath) {
            $file = BASE_PATH . '/public/' . ltrim($avatarPath, '/');

            if (is_file($file)) {
                @unlink($file);
            }
        }

        $userModel->updateAvatar($userId, null);
    }
}
