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
use App\Services\InconsistencyService;

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
            $todayDayOff,
            $user['created_at'] ?? null
        );

        /*
         * Comparação diária:
         * compara as horas trabalhadas hoje com o dia anterior.
         * Se ontem não teve nenhuma hora registrada, a porcentagem não é
         * calculada para evitar divisão por zero e resultados enganosos.
         */
        $yesterdayDate = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
        $yesterdayHoliday = $holidayModel->findByDate($yesterdayDate);
        $yesterdayDayOff = $dayOffModel->findForDate(
            (int) $user['id'],
            $yesterdayDate
        );

        $yesterdaySummary = $entryModel->summarizeDay(
            (int) $user['id'],
            $yesterdayDate,
            $dailyMinutes,
            $toleranceMinutes,
            $yesterdayHoliday,
            $yesterdayDayOff,
            $user['created_at'] ?? null
        );

        $dailyChangePercent = null;

        if ($yesterdaySummary['worked'] > 0) {
            $dailyChangePercent = (
                ($todaySummary['worked'] - $yesterdaySummary['worked'])
                / $yesterdaySummary['worked']
            ) * 100;
        }


        /*
         * Navegação do gráfico semanal.
         * 0 = semana atual
         * -1 = semana passada
         * 1 = próxima semana
         */
        $weekOffset = filter_input(
            INPUT_GET,
            'week_offset',
            FILTER_VALIDATE_INT
        );

        $weekOffset = $weekOffset === false || $weekOffset === null
            ? 0
            : max(-52, min(52, $weekOffset));

        $currentMonday = (new DateTimeImmutable('monday this week'))
            ->setTime(0, 0, 0);

        $monday = $currentMonday->modify(
            ($weekOffset >= 0 ? '+' : '') . $weekOffset . ' weeks'
        );

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
                $dayOffModel->findForDate((int) $user['id'], $dateString),
                $user['created_at'] ?? null
            );

            $weekTotal += $summary['worked'];

            $week[] = [
                'date' => $dateString,
                'day' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'][$i],
                'label' => $date->format('d/m'),
                'minutes' => $summary['worked'],
            ];
        }

        $weekEnd = $monday->modify('+6 days');

        $weekPeriodLabel = $weekOffset === 0
            ? 'Esta semana'
            : (
                $weekOffset === -1
                    ? 'Semana passada'
                    : (
                        $weekOffset === 1
                            ? 'Próxima semana'
                            : $monday->format('d/m') . ' – ' . $weekEnd->format('d/m')
                    )
            );

        $monthStartDate = (new DateTimeImmutable('first day of this month'))
            ->setTime(0, 0, 0);

        $monthEndDate = (new DateTimeImmutable('last day of this month'))
            ->setTime(0, 0, 0);

        $todayDate = (new DateTimeImmutable('today'))
            ->setTime(0, 0, 0);

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
                $dayOff,
                $user['created_at'] ?? null
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


        /*
         * Comparação de horas extras com o mês anterior.
         * Soma horas extras 50% + 100% em ambos os períodos.
         */
        $previousMonthStart = $monthStartDate->modify('-1 month');
        $previousMonthEnd = $previousMonthStart->modify('last day of this month');

        $holidayModel->ensureYear((int) $previousMonthStart->format('Y'));

        $previousMonthPeriod = new DatePeriod(
            $previousMonthStart,
            new DateInterval('P1D'),
            $previousMonthEnd->modify('+1 day')
        );

        $previousMonthExtra = 0;

        foreach ($previousMonthPeriod as $date) {
            $dateString = $date->format('Y-m-d');

            $summary = $entryModel->summarizeDay(
                (int) $user['id'],
                $dateString,
                $dailyMinutes,
                $toleranceMinutes,
                $holidayModel->findByDate($dateString),
                $dayOffModel->findForDate(
                    (int) $user['id'],
                    $dateString
                ),
                $user['created_at'] ?? null
            );

            $previousMonthExtra +=
                $summary['overtime50']
                + $summary['overtime100'];
        }

        $currentMonthExtra = $monthExtra50 + $monthExtra100;

        /*
         * Estimativa financeira das horas extras.
         * Valor hora = salário mensal / horas mensais.
         * Extra 50% = valor hora × 1,5.
         * Extra 100% = valor hora × 2.
         */
        $salary = (float) ($user['salary'] ?? 0);
        $monthlyHours = (int) ($user['monthly_hours'] ?? 0);

        $hourlyValue = null;
        $estimatedExtra50Value = null;
        $estimatedExtra100Value = null;
        $estimatedExtraTotalValue = null;

        if ($salary > 0 && $monthlyHours > 0) {
            $hourlyValue = $salary / $monthlyHours;

            $estimatedExtra50Value =
                ($monthExtra50 / 60) * $hourlyValue * 1.5;

            $estimatedExtra100Value =
                ($monthExtra100 / 60) * $hourlyValue * 2;

            $estimatedExtraTotalValue =
                $estimatedExtra50Value + $estimatedExtra100Value;
        }

        $extraMonthChangePercent = null;

        if ($previousMonthExtra > 0) {
            $extraMonthChangePercent = (
                ($currentMonthExtra - $previousMonthExtra)
                / $previousMonthExtra
            ) * 100;
        }

        $hour = (int) date('G');

        $greeting = match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $inconsistencies = (new InconsistencyService())
            ->forUser($user, 30);

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'user' => $user,
            'inconsistencies' => $inconsistencies,
            'settings' => $settings,
            'greeting' => $greeting,
            'status' => $status,
            'todaySummary' => $todaySummary,
            'yesterdaySummary' => $yesterdaySummary,
            'dailyChangePercent' => $dailyChangePercent,
            'todayHoliday' => $todayHoliday,
            'todayDayOff' => $todayDayOff,
            'week' => $week,
            'weekTotal' => $weekTotal,
            'weekOffset' => $weekOffset,
            'weekPeriodLabel' => $weekPeriodLabel,
            'weekStart' => $monday->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'monthWorked' => $monthWorked,
            'monthExtra50' => $monthExtra50,
            'monthExtra100' => $monthExtra100,
            'previousMonthExtra' => $previousMonthExtra,
            'extraMonthChangePercent' => $extraMonthChangePercent,
            'hourlyValue' => $hourlyValue,
            'estimatedExtra50Value' => $estimatedExtra50Value,
            'estimatedExtra100Value' => $estimatedExtra100Value,
            'estimatedExtraTotalValue' => $estimatedExtraTotalValue,
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
