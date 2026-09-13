<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DayOff;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSettings;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class ReportsController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) authUser()['id'];
        $user = (new User())->find($userId);
        $entryModel = new TimeEntry();
        $holidayModel = new Holiday();
        $dayOffModel = new DayOff();
        $settings = (new WorkSettings())->all();

        $month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');

        $monthStart = (new DateTimeImmutable($month . '-01'))->setTime(0, 0, 0);
        $monthEnd = $monthStart->modify('last day of this month')->setTime(0, 0, 0);
        $today = (new DateTimeImmutable('today'))->setTime(0, 0, 0);

        $holidayModel->ensureYear((int) $monthStart->format('Y'));

        $dailyMinutes = (int) ($user['daily_minutes'] ?? 528);
        $toleranceMinutes = (int) ($settings['tolerance_minutes'] ?? 5);

        $accountCreatedDate = !empty($user['created_at'])
            ? (new DateTimeImmutable((string)$user['created_at']))->setTime(0, 0, 0)
            : null;

        $period = new DatePeriod(
            $monthStart,
            new DateInterval('P1D'),
            $monthEnd->modify('+1 day')
        );

        $rows = [];
        $totalWorked = 0;
        $totalExpected = 0;
        $totalExtra50 = 0;
        $totalExtra100 = 0;
        $totalBank = 0;
        $daysWorked = 0;
        $absences = 0;
        $justifiedDays = 0;
        $holidays = 0;
        $completedDays = 0;

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');

            if ($date > $today) {
                continue;
            }

            $holiday = $holidayModel->findByDate($dateString);
            $dayOff = $dayOffModel->findForDate($userId, $dateString);

            $summary = $entryModel->summarizeDay(
                $userId,
                $dateString,
                $dailyMinutes,
                $toleranceMinutes,
                $holiday,
                $dayOff,
                $user['created_at'] ?? null
            );

            $isAfterCreation = $accountCreatedDate === null
                || $date > $accountCreatedDate
                || $summary['hasEntries'];

            if (!$isAfterCreation) {
                continue;
            }

            $expectedForReport = $summary['expected'];

            $totalWorked += $summary['worked'];
            $totalExpected += $expectedForReport;
            $totalExtra50 += $summary['overtime50'];
            $totalExtra100 += $summary['overtime100'];
            $totalBank += $summary['bankBalance'];

            if ($summary['worked'] > 0) {
                $daysWorked++;
            }

            if ($summary['absence']) {
                $absences++;
            }

            if ($dayOff !== null) {
                $justifiedDays++;
            }

            if ($holiday !== null) {
                $holidays++;
            }

            if ($summary['completed']) {
                $completedDays++;
            }

            $status = 'Sem registro';
            $statusClass = 'neutral';

            if ($holiday !== null) {
                $status = 'Feriado';
                $statusClass = 'holiday';
            }

            if ($dayOff !== null) {
                $status = ucfirst(str_replace('_', ' ', (string)$dayOff['type']));
                $statusClass = 'justified';
            }

            if ($summary['absence']) {
                $status = 'Ausência';
                $statusClass = 'absence';
            } elseif ($summary['completed']) {
                $status = 'Finalizado';
                $statusClass = 'completed';
            } elseif ($summary['hasEntries']) {
                $status = 'Incompleto';
                $statusClass = 'pending';
            }

            $rows[] = [
                'date' => $dateString,
                'weekday' => (int)$date->format('N'),
                'worked' => $summary['worked'],
                'expected' => $expectedForReport,
                'extra50' => $summary['overtime50'],
                'extra100' => $summary['overtime100'],
                'bank' => $summary['bankBalance'],
                'status' => $status,
                'statusClass' => $statusClass,
            ];
        }

        $salary = (float) ($user['salary'] ?? 0);
        $monthlyHours = (int) ($user['monthly_hours'] ?? 0);

        $hourlyValue = null;
        $estimated50 = null;
        $estimated100 = null;
        $estimatedTotal = null;

        if ($salary > 0 && $monthlyHours > 0) {
            $hourlyValue = $salary / $monthlyHours;
            $estimated50 = ($totalExtra50 / 60) * $hourlyValue * 1.5;
            $estimated100 = ($totalExtra100 / 60) * $hourlyValue * 2;
            $estimatedTotal = $estimated50 + $estimated100;
        }

        $averageWorked = $daysWorked > 0
            ? (int) round($totalWorked / $daysWorked)
            : 0;

        $previousMonth = $monthStart->modify('-1 month')->format('Y-m');
        $nextMonth = $monthStart->modify('+1 month')->format('Y-m');

        $this->view('reports/index', [
            'title' => 'Relatórios',
            'active' => 'reports',
            'user' => $user,
            'month' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'rows' => $rows,
            'totalWorked' => $totalWorked,
            'totalExpected' => $totalExpected,
            'totalExtra50' => $totalExtra50,
            'totalExtra100' => $totalExtra100,
            'totalBank' => $totalBank,
            'daysWorked' => $daysWorked,
            'absences' => $absences,
            'justifiedDays' => $justifiedDays,
            'holidays' => $holidays,
            'completedDays' => $completedDays,
            'averageWorked' => $averageWorked,
            'hourlyValue' => $hourlyValue,
            'estimated50' => $estimated50,
            'estimated100' => $estimated100,
            'estimatedTotal' => $estimatedTotal,
        ]);
    }
}
