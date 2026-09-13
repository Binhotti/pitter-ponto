<?php
function fm(int $minutes): string {
    return intdiv($minutes, 60) . 'h ' . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT) . 'min';
}

function fms(int $minutes): string {
    if ($minutes === 0) return '0h 00min';
    $sign = $minutes > 0 ? '+' : '-';
    $minutes = abs($minutes);
    return $sign . intdiv($minutes, 60) . 'h ' . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT) . 'min';
}
$labels = [
    'clock_in' => 'Entrada',
    'lunch_start' => 'Início do almoço',
    'lunch_end' => 'Volta do almoço',
    'clock_out' => 'Saída',
];
?>
<div class="page-heading">
    <div><h1>Histórico</h1><p>Consulte seus registros e horas calculadas por dia.</p></div>
</div>

<section class="panel">
    <form class="filter-row" method="GET" action="<?= config('app.url') ?>">
        <input type="hidden" name="route" value="history">
        <label>De<input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>Até<input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="primary-button compact" type="submit">Filtrar</button>
    </form>
</section>

<section class="history-list">
    <?php if ($days === []): ?>
        <div class="panel empty-state">Nenhum registro encontrado no período.</div>
    <?php else: ?>
        <?php foreach (array_reverse($days, true) as $date => $day): ?>
            <article class="panel history-day">
                <div class="history-day-head">
                    <div><strong><?= date('d/m/Y', strtotime($date)) ?></strong><small><?= date('l', strtotime($date)) ?></small></div>
                    <div class="history-totals">
                        <span>Trabalhado <b><?= fm($day['summary']['worked']) ?></b></span>
                        <span>Extra 50% <b><?= fm($day['summary']['overtime50']) ?></b></span>
                        <span>Extra 100% <b><?= fm($day['summary']['overtime100']) ?></b></span>
                        <span>Banco <b><?= fms($day['summary']['bankBalance']) ?></b></span>
                    </div>
                </div>
                <div class="history-events">
                    <?php foreach ($day['entries'] as $entry): ?>
                        <div><span><?= e($labels[$entry['entry_type']] ?? 'Registro') ?></span><strong><?= date('H:i', strtotime($entry['recorded_at'])) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
