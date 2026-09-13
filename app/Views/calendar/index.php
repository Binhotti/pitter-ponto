<?php
$monthName = date('m/Y', strtotime($month . '-01'));
$prev = (new DateTimeImmutable($month . '-01'))->modify('-1 month')->format('Y-m');
$next = (new DateTimeImmutable($month . '-01'))->modify('+1 month')->format('Y-m');

$entryLabels = [
    'clock_in' => 'Entrada',
    'lunch_start' => 'Início do almoço',
    'lunch_end' => 'Volta do almoço',
    'clock_out' => 'Saída',
];

$entryIcons = [
    'clock_in' => 'log-in',
    'lunch_start' => 'utensils',
    'lunch_end' => 'coffee',
    'clock_out' => 'log-out',
];

$weekdayNames = [
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
    7 => 'Domingo',
];

function calendarMinutes(int $minutes): string
{
    return intdiv(abs($minutes), 60)
        . 'h '
        . str_pad((string)(abs($minutes) % 60), 2, '0', STR_PAD_LEFT)
        . 'min';
}

function calendarSigned(int $minutes): string
{
    if ($minutes === 0) {
        return '0h 00min';
    }

    return ($minutes > 0 ? '+' : '-') . calendarMinutes($minutes);
}
?>
<div class="page-heading">
    <div>
        <h1>Calendário</h1>
        <p>Clique em um dia para ver todos os detalhes da sua jornada.</p>
    </div>

    <div class="month-nav">
        <a href="<?= url('calendar') . '&month=' . e($prev) ?>">←</a>
        <strong><?= e($monthName) ?></strong>
        <a href="<?= url('calendar') . '&month=' . e($next) ?>">→</a>
    </div>
</div>

<section class="panel full-calendar interactive-calendar">
    <div class="full-weekdays">
        <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span>
        <span>Sex</span><span>Sáb</span><span>Dom</span>
    </div>

    <div class="full-days">
        <?php for ($i = 1; $i < $firstWeekday; $i++): ?>
            <div class="calendar-cell muted-cell"></div>
        <?php endfor; ?>

        <?php foreach ($calendar as $day):
            $worked = $day['summary']['worked'];
            $hasExtra = ($day['summary']['overtime50'] + $day['summary']['overtime100']) > 0;
            $class = $worked > 0 ? 'worked-day' : '';

            if ($hasExtra) $class .= ' extra-day';
            if ($day['summary']['absence']) $class .= ' absence-day';
            if (!empty($day['holiday'])) $class .= ' holiday-cell';
            if (!empty($day['dayOff'])) $class .= ' dayoff-cell';

            $entries = [];
            foreach ($day['entries'] as $entry) {
                $entries[] = [
                    'label' => $entryLabels[$entry['entry_type']] ?? 'Registro',
                    'icon' => $entryIcons[$entry['entry_type']] ?? 'circle',
                    'time' => date('H:i', strtotime($entry['recorded_at'])),
                    'manual' => ($entry['source'] ?? 'web') === 'manual',
                ];
            }

            $detail = [
                'date' => $day['date'],
                'dateLabel' => date('d/m/Y', strtotime($day['date'])),
                'weekday' => $weekdayNames[$day['weekday']] ?? '',
                'entries' => $entries,
                'worked' => calendarMinutes($worked),
                'extra50' => calendarMinutes($day['summary']['overtime50']),
                'extra100' => calendarMinutes($day['summary']['overtime100']),
                'bank' => calendarSigned($day['summary']['bankBalance']),
                'bankRaw' => $day['summary']['bankBalance'],
                'estimated' => $day['estimatedExtraValue'] !== null
                    ? 'R$ ' . number_format($day['estimatedExtraValue'], 2, ',', '.')
                    : null,
                'absence' => $day['summary']['absence'],
                'holiday' => $day['holiday']['name'] ?? null,
                'dayOff' => !empty($day['dayOff'])
                    ? ucfirst(str_replace('_', ' ', $day['dayOff']['type']))
                    : null,
                'completed' => $day['summary']['completed'],
                'historyUrl' => url('history')
                    . '&from=' . $day['date']
                    . '&to=' . $day['date'],
            ];

            $detailJson = htmlspecialchars(
                json_encode(
                    $detail,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                ),
                ENT_QUOTES,
                'UTF-8'
            );
        ?>
            <button
                type="button"
                class="calendar-cell calendar-day-button <?= e(trim($class)) ?>"
                data-calendar-day
                data-calendar-detail="<?= $detailJson ?>"
                aria-label="Ver detalhes de <?= date('d/m/Y', strtotime($day['date'])) ?>"
            >
                <strong><?= (int)$day['day'] ?></strong>

                <?php if (!empty($day['holiday'])): ?>
                    <span class="calendar-tag holiday-tag"><?= e($day['holiday']['name']) ?></span>
                <?php elseif (!empty($day['dayOff'])): ?>
                    <span class="calendar-tag dayoff-tag">
                        <?= e(ucfirst(str_replace('_', ' ', $day['dayOff']['type']))) ?>
                    </span>
                <?php elseif ($day['summary']['absence']): ?>
                    <span class="calendar-tag absence-tag">Ausência</span>
                <?php endif; ?>

                <?php if ($worked > 0): ?>
                    <small><?= e(calendarMinutes($worked)) ?></small>
                    <i></i>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<div class="calendar-detail-modal" id="calendar-detail-modal" aria-hidden="true">
    <div class="calendar-detail-backdrop" data-calendar-close></div>

    <section class="calendar-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="calendar-detail-title">
        <button class="calendar-detail-close" type="button" data-calendar-close aria-label="Fechar">
            <i data-lucide="x"></i>
        </button>

        <header class="calendar-detail-head">
            <span class="calendar-detail-icon">
                <i data-lucide="calendar-days"></i>
            </span>

            <div>
                <h2 id="calendar-detail-title">Detalhes do dia</h2>
                <p id="calendar-detail-subtitle"></p>
            </div>
        </header>

        <div class="calendar-detail-status" id="calendar-detail-status"></div>

        <div class="calendar-detail-entries" id="calendar-detail-entries"></div>

        <div class="calendar-detail-summary">
            <div><span>Trabalhado</span><strong id="cal-worked">0h 00min</strong></div>
            <div><span>Extra 50%</span><strong id="cal-extra50">0h 00min</strong></div>
            <div><span>Extra 100%</span><strong id="cal-extra100">0h 00min</strong></div>
            <div><span>Banco</span><strong id="cal-bank">0h 00min</strong></div>
        </div>

        <div class="calendar-detail-money" id="calendar-detail-money" hidden>
            <i data-lucide="wallet-cards"></i>
            <span>Valor estimado das extras</span>
            <strong id="cal-estimated"></strong>
        </div>

        <div class="calendar-detail-actions">
            <button class="secondary-button" type="button" data-calendar-close>Fechar</button>
            <a class="primary-button" id="calendar-history-link" href="<?= url('history') ?>">
                Ver no histórico
                <i data-lucide="arrow-right"></i>
            </a>
        </div>
    </section>
</div>
