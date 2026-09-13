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

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $userModel = new User();
        $entryModel = new TimeEntry();
        $settingsModel = new WorkSettings();
        $holidayModel = new Holiday();
        $dayOffModel = new DayOff();

        $user = $userModel->find((int) authUser()['id']);
        $settings = $settingsModel->all();

        $today = date('Y-m-d');
        $dailyMinutes = (int) ($user['daily_minutes'] ?? 528);
        $toleranceMinutes = (int) ($settings['tolerance_minutes'] ?? 5);

        $holidayModel->ensureYear((int) date('Y'));
        $holidayModel->ensureYear((int) date('Y') + 1);

        $todayHoliday = $holidayModel->findByDate($today);
        $todayDayOff = $dayOffModel->findForDate((int) $user['id'], $today);

        $status = $entryModel->currentStatus((int) $user['id']);

        $todaySummary = $entryModel->summarizeDay(
            (int) $user['id'],
            $today,
            $dailyMinutes,
            $toleranceMinutes,
            $todayHoliday,
            $todayDayOff
        );

        $monday = (new DateTimeImmutable('monday this week'))->setTime(0, 0);

        $week = [];
        $weekTotal = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = $monday->modify("+{$i} days");
            $dateString = $date->format('Y-m-d');

            $summary = $entryModel->summarizeDay(
                (int) $user['id'],
                $dateString,
                $dailyMinutes,
                $toleranceMinutes,
                $holidayModel->findByDate($dateString),
                $dayOffModel->findForDate((int) $user['id'], $dateString)
            );

            $weekTotal += $summary['worked'];

            $week[] = [
                'date' => $dateString,
                'day' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'][$i],
                'label' => $date->format('d/m'),
                'minutes' => $summary['worked'],
            ];
        }

        $monthStartDate = new DateTimeImmutable('first day of this month');
        $monthEndDate = new DateTimeImmutable('last day of this month');
        $todayDate = new DateTimeImmutable('today');

        $period = new DatePeriod(
            $monthStartDate,
            new DateInterval('P1D'),
            $monthEndDate->modify('+1 day')
        );

        $monthWorked = 0;
        $monthExtra50 = 0;
        $monthExtra100 = 0;
        $monthDeficit = 0;
        $monthBankBalance = 0;
        $monthAbsences = 0;
        $saturdaysWorked = 0;
        $monthCalendar = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $holiday = $holidayModel->findByDate($dateString);
            $dayOff = $dayOffModel->findForDate(
                (int) $user['id'],
                $dateString
            );

            $summary = $entryModel->summarizeDay(
                (int) $user['id'],
                $dateString,
                $dailyMinutes,
                $toleranceMinutes,
                $holiday,
                $dayOff
            );

            if ($date <= $todayDate) {
                $monthWorked += $summary['worked'];
                $monthExtra50 += $summary['overtime50'];
                $monthExtra100 += $summary['overtime100'];
                $monthDeficit += $summary['deficit'];
                $monthBankBalance += $summary['bankBalance'];

                if ($summary['absence']) {
                    $monthAbsences++;
                }

                if (
                    $summary['isSaturday']
                    && $summary['completed']
                    && $summary['worked'] > 0
                ) {
                    $saturdaysWorked++;
                }
            }

            $monthCalendar[(int) $date->format('j')] = [
                'worked' => $summary['worked'],
                'extra' => (
                    $summary['overtime50']
                    + $summary['overtime100']
                ) > 0,
                'deficit' => $summary['deficit'] > 0,
                'completed' => $summary['completed'],
                'absence' => $summary['absence'],
                'holiday' => $holiday,
                'dayOff' => $dayOff,
            ];
        }

        $hour = (int) date('G');

        $greeting = match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'user' => $user,
            'settings' => $settings,
            'greeting' => $greeting,
            'status' => $status,
            'todaySummary' => $todaySummary,
            'todayHoliday' => $todayHoliday,
            'todayDayOff' => $todayDayOff,
            'week' => $week,
            'weekTotal' => $weekTotal,
            'monthWorked' => $monthWorked,
            'monthExtra50' => $monthExtra50,
            'monthExtra100' => $monthExtra100,
            'monthDeficit' => $monthDeficit,
            'monthBankBalance' => $monthBankBalance,
            'monthAbsences' => $monthAbsences,
            'saturdaysWorked' => $saturdaysWorked,
            'monthCalendar' => $monthCalendar,
            'monthFirstWeekday' => (int) $monthStartDate->format('w'),
            'daysInMonth' => (int) $monthEndDate->format('j'),
            'recent' => $entryModel->recent((int) $user['id'], 6),
            'success' => flash('success'),
            'error' => flash('error'),
        ]);
    }
}
