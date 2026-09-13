<?php
$typeLabels = [
    'falta_justificada' => 'Falta justificada',
    'atestado' => 'Atestado',
    'folga' => 'Folga',
    'ferias' => 'Férias',
    'compensacao' => 'Compensação',
];

$typeIcons = [
    'falta_justificada' => 'file-check-2',
    'atestado' => 'stethoscope',
    'folga' => 'coffee',
    'ferias' => 'palmtree',
    'compensacao' => 'refresh-cw',
];

$typeClasses = [
    'falta_justificada' => 'justified',
    'atestado' => 'medical',
    'folga' => 'day-off',
    'ferias' => 'vacation',
    'compensacao' => 'compensation',
];

$createdDate = substr((string)($user['created_at'] ?? date('Y-m-d')), 0, 10);
?>

<div class="page-heading">
    <div>
        <h1>Ausências e Justificativas</h1>
        <p>Registre folgas, férias, atestados e faltas justificadas para manter seu banco de horas correto.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<section class="panel absence-info">
    <i data-lucide="info"></i>
    <div>
        <strong>Como funciona</strong>
        <p>
            Dias úteis sem ponto são tratados como ausência automaticamente.
            Cadastre aqui somente quando houver uma justificativa, folga, férias,
            atestado ou compensação. Esses períodos deixam de gerar débito no banco de horas.
        </p>
    </div>
</section>

<section class="panel absence-create-panel">
    <div class="panel-title-row">
        <div>
            <h3>Adicionar ausência ou justificativa</h3>
            <p>Você pode registrar um único dia ou um período completo.</p>
        </div>
    </div>

    <form method="POST" action="<?= url('absence-create') ?>" class="absence-create-form">
        <label>
            Tipo
            <select name="type" required>
                <option value="falta_justificada">Falta justificada</option>
                <option value="atestado">Atestado</option>
                <option value="folga">Folga</option>
                <option value="ferias">Férias</option>
                <option value="compensacao">Compensação</option>
            </select>
        </label>

        <label>
            Data inicial
            <input
                type="date"
                name="start_date"
                min="<?= e($createdDate) ?>"
                value="<?= date('Y-m-d') ?>"
                required
            >
        </label>

        <label>
            Data final
            <input
                type="date"
                name="end_date"
                min="<?= e($createdDate) ?>"
                value="<?= date('Y-m-d') ?>"
                required
            >
        </label>

        <label class="absence-note-field">
            Observação
            <input
                type="text"
                name="note"
                maxlength="255"
                placeholder="Ex.: consulta médica, folga compensatória..."
            >
        </label>

        <button class="primary-button compact absence-submit" type="submit">
            Adicionar
        </button>
    </form>
</section>

<section class="absence-list-section">
    <div class="absence-list-heading">
        <div>
            <h2>Meus registros</h2>
            <p><?= count($items) ?> ocorrência(s) cadastrada(s)</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="panel empty-state absence-empty">
            <i data-lucide="calendar-check-2"></i>
            <strong>Nenhuma ausência ou justificativa cadastrada.</strong>
            <span>Quando precisar, use o formulário acima.</span>
        </div>
    <?php else: ?>
        <div class="absence-cards">
            <?php foreach ($items as $item):
                $label = $typeLabels[$item['type']] ?? 'Ocorrência';
                $icon = $typeIcons[$item['type']] ?? 'calendar';
                $class = $typeClasses[$item['type']] ?? 'default';
            ?>
                <article class="panel absence-card">
                    <div class="absence-card-summary">
                        <span class="absence-type-icon <?= e($class) ?>">
                            <i data-lucide="<?= e($icon) ?>"></i>
                        </span>

                        <div class="absence-card-main">
                            <div class="absence-card-title">
                                <strong><?= e($label) ?></strong>
                                <span class="absence-period">
                                    <?php if ($item['start_date'] === $item['end_date']): ?>
                                        <?= date('d/m/Y', strtotime($item['start_date'])) ?>
                                    <?php else: ?>
                                        <?= date('d/m/Y', strtotime($item['start_date'])) ?>
                                        → <?= date('d/m/Y', strtotime($item['end_date'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <p>
                                <?= !empty($item['note'])
                                    ? e($item['note'])
                                    : 'Sem observação.' ?>
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="<?= url('absence-update') ?>" class="absence-edit-form">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                        <label>
                            Tipo
                            <select name="type" required>
                                <?php foreach ($typeLabels as $value => $optionLabel): ?>
                                    <option
                                        value="<?= e($value) ?>"
                                        <?= $item['type'] === $value ? 'selected' : '' ?>
                                    >
                                        <?= e($optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Início
                            <input
                                type="date"
                                name="start_date"
                                min="<?= e($createdDate) ?>"
                                value="<?= e($item['start_date']) ?>"
                                required
                            >
                        </label>

                        <label>
                            Fim
                            <input
                                type="date"
                                name="end_date"
                                min="<?= e($createdDate) ?>"
                                value="<?= e($item['end_date']) ?>"
                                required
                            >
                        </label>

                        <label class="absence-edit-note">
                            Observação
                            <input
                                type="text"
                                name="note"
                                maxlength="255"
                                value="<?= e($item['note'] ?? '') ?>"
                                placeholder="Observação opcional"
                            >
                        </label>

                        <button class="outline-button absence-save-button" type="submit">
                            Salvar
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="<?= url('absence-delete') ?>"
                        class="absence-delete-form"
                        onsubmit="return confirm('Remover este registro?');"
                    >
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <button type="submit" class="absence-delete-button">
                            <i data-lucide="trash-2"></i>
                            Excluir
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
