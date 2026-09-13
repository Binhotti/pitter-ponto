<?php
function formatMinutes(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    return $hours . 'h ' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . 'min';
}

function formatSignedMinutes(int $minutes): string
{
    if ($minutes === 0) {
        return '0h 00min';
    }

    $sign = $minutes > 0 ? '+' : '-';
    $absolute = abs($minutes);

    return $sign
        . intdiv($absolute, 60)
        . 'h '
        . str_pad((string) ($absolute % 60), 2, '0', STR_PAD_LEFT)
        . 'min';
}

function formatCurrencyBr(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

$firstName = explode(' ', trim($user['name']))[0] ?? $user['name'];

$statusClass = match ($status['key']) {
    'working' => 'working',
    'lunch' => 'lunch',
    'finished' => 'finished',
    default => 'not-started',
};

$typeLabels = [
    'clock_in' => ['Entrada', 'entry', 'log-in'],
    'lunch_start' => ['Início do almoço', 'lunch-start', 'utensils'],
    'lunch_end' => ['Volta do almoço', 'lunch-end', 'coffee'],
    'clock_out' => ['Saída do expediente', 'exit', 'log-out'],
];

$workdayStart = substr((string)($user['workday_start'] ?? '08:00:00'), 0, 5);
$workdayEnd = substr((string)($user['workday_end'] ?? '17:48:00'), 0, 5);
$lunchMinutes = (int) ($user['lunch_minutes'] ?? 60);

$monthNames = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];
?>
<div class="dashboard-header">
    <div>
        <h1><?= e($greeting) ?>, <?= e($firstName) ?> <span class="moon">●</span></h1>
        <p>Organize sua jornada e acompanhe suas horas em um só lugar.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($todayHoliday)): ?>
    <div class="special-day-banner holiday-banner">
        <i data-lucide="calendar-heart"></i>
        <div>
            <strong>Hoje é feriado</strong>
            <span><?= e($todayHoliday['name']) ?> • trabalho realizado hoje será considerado 100%</span>
        </div>
    </div>
<?php elseif (!empty($todayDayOff)): ?>
    <div class="special-day-banner dayoff-banner">
        <i data-lucide="calendar-check-2"></i>
        <div>
            <strong>Hoje não possui jornada prevista</strong>
            <span><?= e(ucfirst(str_replace('_', ' ', $todayDayOff['type']))) ?></span>
        </div>
    </div>
<?php endif; ?>


<?php if (!empty($smartNotifications)): ?>
    <div class="smart-notification-stack">
        <?php foreach ($smartNotifications as $notification): ?>
            <a
                href="<?= e($notification['action']) ?>"
                class="smart-notification-card <?= e($notification['type']) ?>"
            >
                <span class="smart-notification-icon">
                    <i data-lucide="<?= e($notification['icon']) ?>"></i>
                </span>

                <span class="smart-notification-copy">
                    <strong><?= e($notification['title']) ?></strong>
                    <small><?= e($notification['message']) ?></small>
                </span>

                <i data-lucide="arrow-right" class="smart-notification-arrow"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="stats-grid">
    <article class="stat-card">
        <span class="stat-icon blue"><i data-lucide="clock-3"></i></span>
        <div>
            <small>Horas hoje</small>
            <strong><?= formatMinutes($todaySummary['worked']) ?></strong>

            <?php if ($dailyChangePercent !== null): ?>
                <?php
                $changeRounded = (int) round($dailyChangePercent);
                $changeClass = $changeRounded > 0
                    ? 'daily-change positive'
                    : ($changeRounded < 0 ? 'daily-change negative' : 'daily-change neutral');

                $changeIcon = $changeRounded > 0
                    ? 'arrow-up'
                    : ($changeRounded < 0 ? 'arrow-down' : 'minus');
                ?>
                <span class="<?= e($changeClass) ?>">
                    <i data-lucide="<?= e($changeIcon) ?>"></i>
                    <?= $changeRounded > 0 ? '+' : '' ?><?= $changeRounded ?>%
                    <em>em relação a ontem</em>
                </span>
            <?php else: ?>
                <span class="daily-change unavailable">
                    <i data-lucide="minus"></i>
                    <em>Sem comparação com ontem</em>
                </span>
            <?php endif; ?>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon red"><i data-lucide="flame"></i></span>
        <div>
            <small>Horas extras</small>
            <strong><?= formatMinutes($monthExtra50 + $monthExtra100) ?></strong>

            <?php if ($extraMonthChangePercent !== null): ?>
                <?php
                $extraChangeRounded = (int) round($extraMonthChangePercent);

                $extraChangeClass = $extraChangeRounded > 0
                    ? 'daily-change positive'
                    : ($extraChangeRounded < 0
                        ? 'daily-change negative'
                        : 'daily-change neutral');

                $extraChangeIcon = $extraChangeRounded > 0
                    ? 'arrow-up'
                    : ($extraChangeRounded < 0 ? 'arrow-down' : 'minus');
                ?>
                <span class="<?= e($extraChangeClass) ?>">
                    <i data-lucide="<?= e($extraChangeIcon) ?>"></i>
                    <?= $extraChangeRounded > 0 ? '+' : '' ?><?= $extraChangeRounded ?>%
                    <em>em relação ao mês passado</em>
                </span>
            <?php else: ?>
                <span class="daily-change unavailable">
                    <i data-lucide="minus"></i>
                    <em>Sem comparação com o mês passado</em>
                </span>
            <?php endif; ?>

            <?php if ($estimatedExtraTotalValue !== null): ?>
                <span class="extra-estimate">
                    <i data-lucide="wallet-cards"></i>
                    <?= e(formatCurrencyBr($estimatedExtraTotalValue)) ?> estimados
                </span>
            <?php else: ?>
                <a class="extra-estimate missing" href="<?= url('profile') ?>">
                    <i data-lucide="wallet-cards"></i>
                    Cadastre seu salário no perfil
                </a>
            <?php endif; ?>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon yellow"><i data-lucide="scale"></i></span>
        <div>
            <small>Banco de horas</small>
            <strong class="<?= $monthBankBalance > 0 ? 'balance-positive' : ($monthBankBalance < 0 ? 'balance-negative' : '') ?>">
                <?= formatSignedMinutes($monthBankBalance) ?>
            </strong>
            <span class="muted">
                <?= $monthDeficit > 0 ? 'Débito: ' . formatMinutes($monthDeficit) : 'Saldo do mês' ?>
            </span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon green"><i data-lucide="calendar-days"></i></span>
        <div>
            <small>Sábados 100%</small>
            <strong><?= (int) $saturdaysWorked ?></strong>
            <span class="muted">Neste mês</span>
        </div>
    </article>
</div>

<section class="punch-card" id="meu-ponto">
    <div class="punch-head">
        <div>
            <h2>Meu Ponto</h2>
            <p>
                Jornada padrão: <?= e($workdayStart) ?> às <?= e($workdayEnd) ?>
                • <?= $lunchMinutes ?> min de almoço
                • tolerância de <?= (int)($settings['tolerance_minutes'] ?? 5) ?> min
            </p>
        </div>

        <div class="punch-meta">
            <span class="status-pill <?= e($statusClass) ?>">
                <i></i><?= e($status['label']) ?>
            </span>
            <span class="date-now">
                <i data-lucide="calendar-days"></i><?= date('d/m/Y') ?>
            </span>
            <span class="time-now">
                <i data-lucide="clock-3"></i><span id="live-clock"><?= date('H:i') ?></span>
            </span>
        </div>
    </div>

    <div class="punch-actions">
        <?php
        $actions = [
            'clock_in' => ['Registrar entrada', 'green', 'log-in'],
            'lunch_start' => ['Iniciar almoço', 'yellow', 'utensils'],
            'lunch_end' => ['Voltar do almoço', 'blue', 'coffee'],
            'clock_out' => ['Finalizar expediente', 'red', 'log-out'],
        ];

        foreach ($actions as $key => [$label, $class, $icon]):
            $enabled = $status['next'] === $key;
        ?>
            <form method="POST" action="<?= url('clock') ?>">
                <input type="hidden" name="type" value="<?= e($key) ?>">
                <button
                    class="punch-button <?= e($class) ?>"
                    type="submit"
                    <?= !$enabled ? 'disabled' : '' ?>
                >
                    <i data-lucide="<?= e($icon) ?>"></i>
                    <strong><?= e($label) ?></strong>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<div class="dashboard-lower-grid">
    <section class="panel week-panel">
        <div class="panel-title-row">
            <div>
                <h3>Minhas horas na semana</h3>
                <p>
                    Total de <?= formatMinutes($weekTotal) ?>
                    • Média de <?= formatMinutes((int) round($weekTotal / 7)) ?>/dia
                    • <?= date('d/m', strtotime($weekStart)) ?> a <?= date('d/m', strtotime($weekEnd)) ?>
                </p>
            </div>
            <form method="GET" action="<?= config('app.url') ?>" class="week-filter-form">
                <input type="hidden" name="route" value="dashboard">

                <label class="week-filter-select">
                    <select
                        name="week_offset"
                        aria-label="Período do gráfico semanal"
                        onchange="this.form.submit()"
                    >
                        <option value="0" <?= $weekOffset === 0 ? 'selected' : '' ?>>
                            Esta semana
                        </option>
                        <option value="-1" <?= $weekOffset === -1 ? 'selected' : '' ?>>
                            Semana passada
                        </option>
                        <option value="-2" <?= $weekOffset === -2 ? 'selected' : '' ?>>
                            Há 2 semanas
                        </option>
                        <option value="-3" <?= $weekOffset === -3 ? 'selected' : '' ?>>
                            Há 3 semanas
                        </option>
                        <option value="-4" <?= $weekOffset === -4 ? 'selected' : '' ?>>
                            Há 4 semanas
                        </option>
                    </select>
                    <i data-lucide="chevron-down"></i>
                </label>
            </form>
        </div>

        <div class="bars">
            <?php
            $max = max(array_column($week, 'minutes') ?: [0]);

            foreach ($week as $day):
                $minutes = (int) $day['minutes'];
                $height = $max > 0
                    ? max(2, (int) round(($minutes / $max) * 100))
                    : 0;
            ?>
                <div class="bar-col">
                    <span class="bar-value"><?= formatMinutes($minutes) ?></span>
                    <div class="bar-track">
                        <div
                            class="bar-fill <?= $minutes === 0 ? 'empty' : '' ?>"
                            style="height: <?= $height ?>%"
                        ></div>
                    </div>
                    <strong><?= e($day['day']) ?></strong>
                    <small><?= e($day['label']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel recent-panel">
        <div class="panel-title-row">
            <h3>Últimos registros</h3>
            <a href="<?= url('history') ?>">Ver todos →</a>
        </div>

        <div class="recent-list">
            <?php if ($recent === []): ?>
                <div class="empty-state">Nenhum ponto registrado ainda.</div>
            <?php else: ?>
                <?php foreach ($recent as $entry):
                    [$label, $iconClass, $iconName] = $typeLabels[$entry['entry_type']]
                        ?? ['Registro', 'entry', 'circle'];
                ?>
                    <div class="recent-item">
                        <span class="recent-icon <?= e($iconClass) ?>">
                            <i data-lucide="<?= e($iconName) ?>"></i>
                        </span>
                        <div>
                            <strong><?= e($label) ?></strong>
                            <small><?= date('d/m/Y, H:i', strtotime($entry['recorded_at'])) ?></small>
                        </div>
                        <time><?= date('H:i', strtotime($entry['recorded_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel calendar-mini">
        <div class="panel-title-row">
            <h3>Meu calendário</h3>
        </div>

        <div class="calendar-mini-header">
            <strong><?= e($monthNames[(int) date('n')]) ?> <?= date('Y') ?></strong>
        </div>

        <div class="mini-weekdays">
            <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
        </div>

        <div class="mini-days">
            <?php for ($i = 0; $i < $monthFirstWeekday; $i++): ?>
                <span class="mini-day"></span>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                $dayData = $monthCalendar[$day] ?? ['worked' => 0, 'extra' => false];
                $classes = ['mini-day'];

                if ($day === (int) date('j')) {
                    $classes[] = 'today';
                }

                if (!empty($dayData['holiday'])) {
                    $classes[] = 'holiday-day';
                }

                if (!empty($dayData['dayOff'])) {
                    $classes[] = 'dayoff-day';
                }

                if (!empty($dayData['absence'])) {
                    $classes[] = 'marker-absence';
                } elseif ($dayData['worked'] > 0) {
                    if ($dayData['deficit']) {
                        $classes[] = 'marker-deficit';
                    } else {
                        $classes[] = $dayData['extra'] ? 'marker-extra' : 'marker-worked';
                    }
                }
            ?>
                <span
                    class="<?= e(implode(' ', $classes)) ?>"
                    title="<?= !empty($dayData['holiday']) ? e($dayData['holiday']['name']) : (!empty($dayData['dayOff']) ? e($dayData['dayOff']['type']) : '') ?>"
                ><?= $day ?></span>
            <?php endfor; ?>
        </div>

        <div class="calendar-legend">
            <span><i class="legend worked"></i>Dia trabalhado</span>
            <span><i class="legend extra"></i>Horas extras</span>
            <span><i class="legend absent"></i>Ausência</span>
            <span><i class="legend holiday"></i>Feriado</span>
        </div>
    </section>
</div>

<section class="evolution-card">
    <div>
        <span class="evolution-icon"><i data-lucide="chart-no-axes-column-increasing"></i></span>
        <div>
            <strong>Acompanhe sua evolução</strong>
            <small>Confira seus relatórios e veja seu desempenho.</small>
        </div>
    </div>
    <a href="<?= url('history') ?>" class="outline-button">Ver relatórios →</a>
</section>
