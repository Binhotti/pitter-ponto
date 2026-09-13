<div class="page-heading">
    <div><h1>Configurações</h1><p>Defina as regras gerais usadas pelo controle de jornada.</p></div>
</div>

<?php if (!empty($success)): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

<section class="panel form-panel">
    <form method="POST" action="<?= url('settings') ?>" class="settings-form">
        <div class="form-grid">
            <label>Início do expediente
                <input type="time" name="workday_start" value="<?= e($settings['workday_start'] ?? '08:00') ?>">
            </label>
            <label>Fim do expediente
                <input type="time" name="workday_end" value="<?= e($settings['workday_end'] ?? '17:48') ?>">
            </label>
            <label>Intervalo de almoço (minutos)
                <input type="number" name="lunch_minutes" min="0" value="<?= e($settings['lunch_minutes'] ?? '60') ?>">
            </label>
            <label>Hora extra em dias úteis (%)
                <input type="number" name="overtime_weekday_percent" value="<?= e($settings['overtime_weekday_percent'] ?? '50') ?>">
            </label>
            <label>Hora extra aos sábados (%)
                <input type="number" name="overtime_saturday_percent" value="<?= e($settings['overtime_saturday_percent'] ?? '100') ?>">
            </label>
            <label>Hora extra aos domingos (%)
                <input type="number" name="overtime_sunday_percent" value="<?= e($settings['overtime_sunday_percent'] ?? '100') ?>">
            </label>
            <label>Tolerância (minutos)
                <input type="number" name="tolerance_minutes" value="<?= e($settings['tolerance_minutes'] ?? '5') ?>">
            </label>
        </div>

        <div class="info-note">
            Nesta primeira versão, sábado e domingo já são contabilizados como extra 100% no dashboard.
            Esta tela deixa as regras preparadas para evoluirmos os cálculos nas próximas etapas.
        </div>

        <button class="primary-button compact" type="submit">Salvar configurações</button>
    </form>
</section>
