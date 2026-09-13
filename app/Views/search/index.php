<?php
$entryLabels = [
    'clock_in' => 'Entrada',
    'lunch_start' => 'Início do almoço',
    'lunch_end' => 'Volta do almoço',
    'clock_out' => 'Saída',
];

$totalResults =
    count($shortcuts)
    + count($entries)
    + count($dayOffs)
    + count($holidays)
    + ($dateResult ? 1 : 0);
?>
<div class="page-heading search-page-heading">
    <div>
        <h1>Busca</h1>
        <p>Encontre telas, registros, datas, ausências e feriados.</p>
    </div>
</div>

<form method="GET" action="<?= config('app.url') ?>" class="search-page-form">
    <input type="hidden" name="route" value="search">
    <i data-lucide="search"></i>
    <input
        type="search"
        name="q"
        value="<?= e($query) ?>"
        placeholder="Ex.: relatórios, entrada, 12/09/2026, atestado..."
        autofocus
    >
    <button class="primary-button" type="submit">Buscar</button>
</form>

<?php if ($query === ''): ?>
    <section class="panel search-section">
        <div class="panel-title-row">
            <div>
                <h3>Acesso rápido</h3>
                <p>Você também pode buscar pelo nome de qualquer área do sistema.</p>
            </div>
        </div>

        <div class="search-shortcuts-grid">
            <?php foreach ($shortcuts as $shortcut): ?>
                <a href="<?= e($shortcut['url']) ?>" class="search-shortcut-card">
                    <span><i data-lucide="<?= e($shortcut['icon']) ?>"></i></span>
                    <strong><?= e($shortcut['label']) ?></strong>
                    <i data-lucide="arrow-right"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php elseif ($totalResults === 0): ?>
    <section class="panel empty-state search-empty-state">
        <i data-lucide="search-x"></i>
        <strong>Nenhum resultado para “<?= e($query) ?>”.</strong>
        <span>Tente outro termo ou uma data no formato 12/09/2026.</span>
    </section>
<?php else: ?>
    <div class="search-results-stack">
        <?php if ($shortcuts !== []): ?>
            <section class="panel search-section">
                <h3>Áreas do sistema</h3>
                <div class="search-shortcuts-grid">
                    <?php foreach ($shortcuts as $shortcut): ?>
                        <a href="<?= e($shortcut['url']) ?>" class="search-shortcut-card">
                            <span><i data-lucide="<?= e($shortcut['icon']) ?>"></i></span>
                            <strong><?= e($shortcut['label']) ?></strong>
                            <i data-lucide="arrow-right"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($dateResult): ?>
            <section class="panel search-section">
                <h3>Data encontrada</h3>
                <a href="<?= e($dateResult['url']) ?>" class="search-result-row">
                    <span class="search-result-icon"><i data-lucide="calendar-search"></i></span>
                    <span>
                        <strong><?= date('d/m/Y', strtotime($dateResult['date'])) ?></strong>
                        <small>Ver todas as marcações deste dia no Histórico.</small>
                    </span>
                    <i data-lucide="arrow-right"></i>
                </a>
            </section>
        <?php endif; ?>

        <?php if ($entries !== []): ?>
            <section class="panel search-section">
                <h3>Registros de ponto</h3>

                <?php foreach ($entries as $entry):
                    $date = substr($entry['recorded_at'], 0, 10);
                ?>
                    <a
                        href="<?= url('history') . '&from=' . e($date) . '&to=' . e($date) ?>"
                        class="search-result-row"
                    >
                        <span class="search-result-icon"><i data-lucide="clock-3"></i></span>
                        <span>
                            <strong><?= e($entryLabels[$entry['entry_type']] ?? 'Registro') ?></strong>
                            <small>
                                <?= date('d/m/Y H:i', strtotime($entry['recorded_at'])) ?>
                                <?php if (!empty($entry['note'])): ?>
                                    • <?= e($entry['note']) ?>
                                <?php endif; ?>
                            </small>
                        </span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($dayOffs !== []): ?>
            <section class="panel search-section">
                <h3>Ausências e justificativas</h3>

                <?php foreach ($dayOffs as $item): ?>
                    <a href="<?= url('absences') ?>" class="search-result-row">
                        <span class="search-result-icon"><i data-lucide="file-check-2"></i></span>
                        <span>
                            <strong><?= e(ucfirst(str_replace('_', ' ', $item['type']))) ?></strong>
                            <small>
                                <?= date('d/m/Y', strtotime($item['start_date'])) ?>
                                <?php if ($item['end_date'] !== $item['start_date']): ?>
                                    → <?= date('d/m/Y', strtotime($item['end_date'])) ?>
                                <?php endif; ?>
                                <?php if (!empty($item['note'])): ?>
                                    • <?= e($item['note']) ?>
                                <?php endif; ?>
                            </small>
                        </span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($holidays !== []): ?>
            <section class="panel search-section">
                <h3>Feriados</h3>

                <?php foreach ($holidays as $holiday): ?>
                    <a
                        href="<?= url('calendar') . '&month=' . date('Y-m', strtotime($holiday['holiday_date'])) ?>"
                        class="search-result-row"
                    >
                        <span class="search-result-icon"><i data-lucide="calendar-heart"></i></span>
                        <span>
                            <strong><?= e($holiday['name']) ?></strong>
                            <small><?= date('d/m/Y', strtotime($holiday['holiday_date'])) ?></small>
                        </span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>
