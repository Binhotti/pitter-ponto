<?php
function fm(int $minutes): string {
    return intdiv($minutes, 60) . 'h ' . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT) . 'min';
}

function fms(int $minutes): string {
    if ($minutes === 0) return '0h 00min';

    $sign = $minutes > 0 ? '+' : '-';
    $minutes = abs($minutes);

    return $sign
        . intdiv($minutes, 60)
        . 'h '
        . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT)
        . 'min';
}

$labels = [
    'clock_in' => 'Entrada',
    'lunch_start' => 'Início do almoço',
    'lunch_end' => 'Volta do almoço',
    'clock_out' => 'Saída',
];

$icons = [
    'clock_in' => 'log-in',
    'lunch_start' => 'utensils',
    'lunch_end' => 'coffee',
    'clock_out' => 'log-out',
];

$classes = [
    'clock_in' => 'entry',
    'lunch_start' => 'lunch-start',
    'lunch_end' => 'lunch-end',
    'clock_out' => 'exit',
];

$weekdays = [
    'Sunday' => 'Domingo',
    'Monday' => 'Segunda-feira',
    'Tuesday' => 'Terça-feira',
    'Wednesday' => 'Quarta-feira',
    'Thursday' => 'Quinta-feira',
    'Friday' => 'Sexta-feira',
    'Saturday' => 'Sábado',
];
?>

<div class="page-heading">
    <div>
        <h1>Histórico</h1>
        <p>Consulte seus registros e horas calculadas por dia.</p>
    </div>
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

        <label>
            De
            <input type="date" name="from" value="<?= e($from) ?>">
        </label>

        <label>
            Até
            <input type="date" name="to" value="<?= e($to) ?>">
        </label>

        <button class="primary-button compact" type="submit">Filtrar</button>
    </form>
</section>

<section class="history-list history-list-modern">
    <?php if ($days === []): ?>
        <div class="panel empty-state">Nenhum registro encontrado no período.</div>
    <?php else: ?>
        <?php foreach (array_reverse($days, true) as $date => $day): ?>
            <?php
            $weekdayEnglish = date('l', strtotime($date));
            $weekday = $weekdays[$weekdayEnglish] ?? $weekdayEnglish;
            ?>
            <article class="panel history-day history-day-modern">
                <div class="history-day-head modern">
                    <div class="history-date-block">
                        <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                        <small><?= e($weekday) ?></small>
                    </div>

                    <div class="history-totals modern-totals">
                        <span>
                            <small>Trabalhado</small>
                            <b><?= fm($day['summary']['worked']) ?></b>
                        </span>

                        <span>
                            <small>Extra 50%</small>
                            <b><?= fm($day['summary']['overtime50']) ?></b>
                        </span>

                        <span>
                            <small>Extra 100%</small>
                            <b><?= fm($day['summary']['overtime100']) ?></b>
                        </span>

                        <span>
                            <small>Banco</small>
                            <b class="<?= $day['summary']['bankBalance'] > 0 ? 'balance-positive' : ($day['summary']['bankBalance'] < 0 ? 'balance-negative' : '') ?>">
                                <?= fms($day['summary']['bankBalance']) ?>
                            </b>
                        </span>
                    </div>
                </div>

                <div class="history-events history-events-compact">
                    <?php foreach ($day['entries'] as $entry): ?>
                        <?php
                        $entryType = $entry['entry_type'];
                        $label = $labels[$entryType] ?? 'Registro';
                        $icon = $icons[$entryType] ?? 'circle';
                        $class = $classes[$entryType] ?? 'entry';
                        ?>
                        <div class="history-point-card">
                            <div class="history-point-left">
                                <span class="recent-icon <?= e($class) ?>">
                                    <i data-lucide="<?= e($icon) ?>"></i>
                                </span>

                                <div>
                                    <small><?= e($label) ?></small>
                                    <strong><?= date('H:i', strtotime($entry['recorded_at'])) ?></strong>

                                    <?php if (($entry['source'] ?? 'web') === 'manual'): ?>
                                        <span class="manual-badge">Ajustado</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="history-edit-trigger"
                                data-point-edit
                                data-entry-id="<?= (int)$entry['id'] ?>"
                                data-date="<?= e($date) ?>"
                                data-label="<?= e($label) ?>"
                                data-time="<?= date('H:i', strtotime($entry['recorded_at'])) ?>"
                            >
                                <i data-lucide="pencil"></i>
                                Editar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<div class="point-edit-modal" id="point-edit-modal" aria-hidden="true">
    <div class="point-edit-backdrop" data-point-edit-close></div>

    <div class="point-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="point-edit-title">
        <button
            type="button"
            class="point-edit-close"
            data-point-edit-close
            aria-label="Fechar"
        >
            <i data-lucide="x"></i>
        </button>

        <div class="point-edit-head">
            <span class="point-edit-icon">
                <i data-lucide="pencil-line"></i>
            </span>

            <div>
                <h2 id="point-edit-title">Editar marcação</h2>
                <p id="point-edit-description">Ajuste o horário e informe o motivo.</p>
            </div>
        </div>

        <form method="POST" action="<?= url('adjustment-update') ?>" class="point-edit-form">
            <input type="hidden" name="entry_id" id="edit-entry-id">
            <input type="hidden" name="date" id="edit-entry-date">

            <label>
                Registro
                <input type="text" id="edit-entry-label" disabled>
            </label>

            <label>
                Novo horário
                <input type="time" name="time" id="edit-entry-time" required>
            </label>

            <label class="point-edit-reason">
                Motivo da alteração
                <textarea
                    name="reason"
                    rows="3"
                    minlength="3"
                    placeholder="Ex.: esqueci de bater o ponto no horário correto"
                    required
                ></textarea>
            </label>

            <div class="point-edit-actions">
                <button
                    type="button"
                    class="secondary-button"
                    data-point-edit-close
                >
                    Cancelar
                </button>

                <button class="primary-button" type="submit">
                    <i data-lucide="save"></i>
                    Salvar alteração
                </button>
            </div>
        </form>
    </div>
</div>
