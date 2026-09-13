<?php
function reportMinutes(int $minutes): string
{
    $hours = intdiv(abs($minutes), 60);
    $mins = abs($minutes) % 60;

    return $hours . 'h ' . str_pad((string)$mins, 2, '0', STR_PAD_LEFT) . 'min';
}

function reportSignedMinutes(int $minutes): string
{
    if ($minutes === 0) {
        return '0h 00min';
    }

    return ($minutes > 0 ? '+' : '-')
        . reportMinutes($minutes);
}

function reportCurrency(?float $value): string
{
    if ($value === null) {
        return '—';
    }

    return 'R$ ' . number_format($value, 2, ',', '.');
}

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

$weekdayNames = [
    1 => 'Seg',
    2 => 'Ter',
    3 => 'Qua',
    4 => 'Qui',
    5 => 'Sex',
    6 => 'Sáb',
    7 => 'Dom',
];

$maxWorked = 0;
foreach ($rows as $row) {
    $maxWorked = max($maxWorked, (int)$row['worked']);
}
?>

<div class="page-heading reports-page-heading">
    <div>
        <h1>Relatórios</h1>
        <p>Acompanhe sua jornada, banco de horas e valores estimados por mês.</p>
    </div>

    <form method="GET" action="<?= config('app.url') ?>" class="report-month-form">
        <input type="hidden" name="route" value="reports">
        <input
            type="month"
            name="month"
            value="<?= e($month) ?>"
            onchange="this.form.submit()"
            aria-label="Selecionar mês"
        >
    </form>
</div>

<div class="report-month-navigation panel">
    <a href="<?= url('reports') ?>&month=<?= e($previousMonth) ?>" class="report-nav-button">
        <i data-lucide="chevron-left"></i>
        Mês anterior
    </a>

    <strong>
        <?= e($monthNames[(int)$monthStart->format('n')]) ?>
        <?= $monthStart->format('Y') ?>
    </strong>

    <a href="<?= url('reports') ?>&month=<?= e($nextMonth) ?>" class="report-nav-button">
        Próximo mês
        <i data-lucide="chevron-right"></i>
    </a>
</div>

<div class="report-stats-grid">
    <article class="stat-card report-stat-card">
        <span class="stat-icon blue"><i data-lucide="clock-3"></i></span>
        <div>
            <small>Horas trabalhadas</small>
            <strong><?= reportMinutes($totalWorked) ?></strong>
            <span class="muted"><?= $daysWorked ?> dia(s) trabalhado(s)</span>
        </div>
    </article>

    <article class="stat-card report-stat-card">
        <span class="stat-icon red"><i data-lucide="flame"></i></span>
        <div>
            <small>Horas extras</small>
            <strong><?= reportMinutes($totalExtra50 + $totalExtra100) ?></strong>
            <span class="muted">
                50%: <?= reportMinutes($totalExtra50) ?> •
                100%: <?= reportMinutes($totalExtra100) ?>
            </span>
        </div>
    </article>

    <article class="stat-card report-stat-card">
        <span class="stat-icon yellow"><i data-lucide="scale"></i></span>
        <div>
            <small>Banco de horas</small>
            <strong class="<?= $totalBank > 0 ? 'balance-positive' : ($totalBank < 0 ? 'balance-negative' : '') ?>">
                <?= reportSignedMinutes($totalBank) ?>
            </strong>
            <span class="muted"><?= $absences ?> ausência(s)</span>
        </div>
    </article>

    <article class="stat-card report-stat-card">
        <span class="stat-icon green"><i data-lucide="wallet-cards"></i></span>
        <div>
            <small>Extras estimadas</small>
            <strong><?= e(reportCurrency($estimatedTotal)) ?></strong>
            <span class="muted">Valor aproximado no mês</span>
        </div>
    </article>
</div>

<div class="reports-main-grid">
    <section class="panel report-chart-panel">
        <div class="panel-title-row">
            <div>
                <h3>Horas por dia</h3>
                <p>
                    Média de <?= reportMinutes($averageWorked) ?>/dia trabalhado
                    • Jornada prevista acumulada: <?= reportMinutes($totalExpected) ?>
                </p>
            </div>
        </div>

        <?php if ($rows === []): ?>
            <div class="empty-state report-empty-chart">
                Nenhum dado disponível para este mês.
            </div>
        <?php else: ?>
            <div class="monthly-bars">
                <?php foreach ($rows as $row):
                    $height = $maxWorked > 0
                        ? max(2, (int) round(($row['worked'] / $maxWorked) * 100))
                        : 0;
                ?>
                    <div class="monthly-bar-col" title="<?= date('d/m/Y', strtotime($row['date'])) ?> • <?= reportMinutes($row['worked']) ?>">
                        <span class="monthly-bar-value">
                            <?= $row['worked'] > 0 ? reportMinutes($row['worked']) : '0h' ?>
                        </span>

                        <div class="monthly-bar-track">
                            <div
                                class="monthly-bar-fill <?= $row['worked'] <= 0 ? 'empty' : '' ?>"
                                style="height: <?= $height ?>%"
                            ></div>
                        </div>

                        <strong><?= date('d', strtotime($row['date'])) ?></strong>
                        <small><?= e($weekdayNames[$row['weekday']] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel report-summary-panel">
        <div class="panel-title-row">
            <div>
                <h3>Resumo do mês</h3>
                <p>Indicadores principais do período.</p>
            </div>
        </div>

        <div class="report-summary-list">
            <div>
                <span><i data-lucide="calendar-check-2"></i> Dias finalizados</span>
                <strong><?= $completedDays ?></strong>
            </div>
            <div>
                <span><i data-lucide="calendar-x-2"></i> Ausências</span>
                <strong><?= $absences ?></strong>
            </div>
            <div>
                <span><i data-lucide="file-check-2"></i> Justificativas / folgas</span>
                <strong><?= $justifiedDays ?></strong>
            </div>
            <div>
                <span><i data-lucide="calendar-heart"></i> Feriados</span>
                <strong><?= $holidays ?></strong>
            </div>
            <div>
                <span><i data-lucide="badge-dollar-sign"></i> Valor da hora</span>
                <strong><?= e(reportCurrency($hourlyValue)) ?></strong>
            </div>
            <div>
                <span><i data-lucide="circle-dollar-sign"></i> Extra 50% estimada</span>
                <strong><?= e(reportCurrency($estimated50)) ?></strong>
            </div>
            <div>
                <span><i data-lucide="circle-dollar-sign"></i> Extra 100% estimada</span>
                <strong><?= e(reportCurrency($estimated100)) ?></strong>
            </div>
        </div>
    </section>
</div>

<section class="panel report-table-panel">
    <div class="panel-title-row">
        <div>
            <h3>Detalhamento diário</h3>
            <p>Todos os dias contabilizados neste relatório.</p>
        </div>
    </div>

    <?php if ($rows === []): ?>
        <div class="empty-state">Nenhum registro encontrado.</div>
    <?php else: ?>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Trabalhado</th>
                        <th>Previsto</th>
                        <th>Extra 50%</th>
                        <th>Extra 100%</th>
                        <th>Banco</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($rows) as $row): ?>
                        <tr>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($row['date'])) ?></strong>
                                <small><?= e($weekdayNames[$row['weekday']] ?? '') ?></small>
                            </td>
                            <td>
                                <span class="report-status <?= e($row['statusClass']) ?>">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>
                            <td><?= reportMinutes($row['worked']) ?></td>
                            <td><?= reportMinutes($row['expected']) ?></td>
                            <td><?= reportMinutes($row['extra50']) ?></td>
                            <td><?= reportMinutes($row['extra100']) ?></td>
                            <td class="<?= $row['bank'] > 0 ? 'balance-positive' : ($row['bank'] < 0 ? 'balance-negative' : '') ?>">
                                <?= reportSignedMinutes($row['bank']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
