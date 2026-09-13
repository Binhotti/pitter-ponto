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


<?php if (!empty($success)): ?>
    <div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<section class="panel adjustment-create-panel">
    <div class="panel-title-row">
        <div>
            <h3>Adicionar ponto esquecido</h3>
            <p>Use somente quando alguma marcação não tiver sido registrada.</p>
        </div>
    </div>

    <form method="POST" action="<?= url('adjustment-create') ?>" class="adjustment-create-form">
        <label>
            Data
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
        </label>

        <label>
            Tipo
            <select name="type" required>
                <option value="clock_in">Entrada</option>
                <option value="lunch_start">Início do almoço</option>
                <option value="lunch_end">Volta do almoço</option>
                <option value="clock_out">Saída</option>
            </select>
        </label>

        <label>
            Horário
            <input type="time" name="time" required>
        </label>

        <label class="adjustment-reason-field">
            Motivo
            <input
                type="text"
                name="reason"
                placeholder="Ex.: esqueci de bater a volta do almoço"
                minlength="3"
                required
            >
        </label>

        <button class="primary-button compact" type="submit">
            Adicionar marcação
        </button>
    </form>
</section>

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
                        <form
                            method="POST"
                            action="<?= url('adjustment-update') ?>"
                            class="history-event-edit"
                        >
                            <input type="hidden" name="entry_id" value="<?= (int)$entry['id'] ?>">
                            <input type="hidden" name="date" value="<?= e($date) ?>">

                            <div class="history-event-main">
                                <span><?= e($labels[$entry['entry_type']] ?? 'Registro') ?></span>
                                <strong><?= date('H:i', strtotime($entry['recorded_at'])) ?></strong>
                                <?php if (($entry['source'] ?? 'web') === 'manual'): ?>
                                    <small class="manual-badge">Ajustado</small>
                                <?php endif; ?>
                            </div>

                            <div class="history-event-editor">
                                <label>
                                    Novo horário
                                    <input
                                        type="time"
                                        name="time"
                                        value="<?= date('H:i', strtotime($entry['recorded_at'])) ?>"
                                        required
                                    >
                                </label>

                                <label>
                                    Motivo
                                    <input
                                        type="text"
                                        name="reason"
                                        placeholder="Por que está alterando?"
                                        minlength="3"
                                        required
                                    >
                                </label>

                                <button class="outline-button edit-point-button" type="submit">
                                    Salvar
                                </button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
