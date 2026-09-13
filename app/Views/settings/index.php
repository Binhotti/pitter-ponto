<?php
$workdayStart = substr((string)($user['workday_start'] ?? '08:00:00'), 0, 5);
$workdayEnd = substr((string)($user['workday_end'] ?? '17:48:00'), 0, 5);
$lunchStartTime = substr((string)($user['lunch_start_time'] ?? '12:00:00'), 0, 5);
$lunchMinutes = (int)($user['lunch_minutes'] ?? 60);
$theme = $user['theme'] ?? 'light';
$avatarPath = $user['avatar_path'] ?? null;

$initials = '';
foreach (explode(' ', trim((string)$user['name'])) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    if (mb_strlen($initials) >= 2) break;
}
?>
<div class="page-heading">
    <div>
        <h1>Configurações</h1>
        <p>Personalize sua jornada, aparência, notificações e perfil.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form
    method="POST"
    action="<?= url('settings') ?>"
    enctype="multipart/form-data"
    class="personal-settings-form"
>
    <section class="panel settings-section">
        <div class="settings-section-head">
            <span class="settings-section-icon blue-soft-icon">
                <i data-lucide="clock-3"></i>
            </span>
            <div>
                <h2>Jornada de trabalho</h2>
                <p>Esses horários serão usados nos cálculos do banco de horas.</p>
            </div>
        </div>

        <div class="settings-grid four">
            <label>
                Horário padrão de entrada
                <input type="time" name="workday_start" value="<?= e($workdayStart) ?>" required>
            </label>

            <label>
                Horário padrão de saída
                <input type="time" name="workday_end" value="<?= e($workdayEnd) ?>" required>
            </label>

            <label>
                Início padrão do almoço
                <input type="time" name="lunch_start_time" value="<?= e($lunchStartTime) ?>" required>
            </label>

            <label>
                Duração do almoço
                <div class="input-with-suffix">
                    <input
                        type="number"
                        name="lunch_minutes"
                        min="0"
                        max="240"
                        value="<?= $lunchMinutes ?>"
                        required
                    >
                    <span>min</span>
                </div>
            </label>
        </div>

        <div class="settings-preview">
            <i data-lucide="calculator"></i>
            <span>
                Jornada líquida atual:
                <strong>
                    <?= intdiv((int)$user['daily_minutes'], 60) ?>h
                    <?= str_pad((string)((int)$user['daily_minutes'] % 60), 2, '0', STR_PAD_LEFT) ?>min
                </strong>
            </span>
        </div>
    </section>

    <section class="panel settings-section">
        <div class="settings-section-head">
            <span class="settings-section-icon yellow-soft-icon">
                <i data-lucide="moon-star"></i>
            </span>
            <div>
                <h2>Aparência</h2>
                <p>Escolha como o Pitter Ponto deve aparecer para você.</p>
            </div>
        </div>

        <div class="theme-options">
            <label class="theme-card">
                <input
                    type="radio"
                    name="theme"
                    value="light"
                    <?= $theme === 'light' ? 'checked' : '' ?>
                >
                <span class="theme-preview light-preview">
                    <i data-lucide="sun"></i>
                </span>
                <span>
                    <strong>Tema claro</strong>
                    <small>Visual atual do sistema</small>
                </span>
            </label>

            <label class="theme-card">
                <input
                    type="radio"
                    name="theme"
                    value="dark"
                    <?= $theme === 'dark' ? 'checked' : '' ?>
                >
                <span class="theme-preview dark-preview">
                    <i data-lucide="moon"></i>
                </span>
                <span>
                    <strong>Tema escuro</strong>
                    <small>Mais confortável à noite</small>
                </span>
            </label>
        </div>
    </section>

    <section class="panel settings-section">
        <div class="settings-section-head">
            <span class="settings-section-icon green-soft-icon">
                <i data-lucide="bell"></i>
            </span>
            <div>
                <h2>Notificações</h2>
                <p>Controle os lembretes exibidos pelo sistema.</p>
            </div>
        </div>

        <div class="settings-toggle-list">
            <label class="settings-toggle-row">
                <div>
                    <strong>Avisos dentro do sistema</strong>
                    <small>Exibe lembretes inteligentes relacionados ao seu ponto.</small>
                </div>
                <span class="switch">
                    <input
                        type="checkbox"
                        name="notifications_enabled"
                        value="1"
                        <?= (int)($user['notifications_enabled'] ?? 1) === 1 ? 'checked' : '' ?>
                    >
                    <span class="switch-slider"></span>
                </span>
            </label>

            <label class="settings-toggle-row">
                <div>
                    <strong>Notificações do navegador</strong>
                    <small>Exibe os lembretes também como notificação do navegador.</small>
                </div>
                <span class="switch">
                    <input
                        type="checkbox"
                        name="browser_notifications"
                        id="browser-notifications"
                        value="1"
                        <?= (int)($user['browser_notifications'] ?? 0) === 1 ? 'checked' : '' ?>
                    >
                    <span class="switch-slider"></span>
                </span>
            </label>
        </div>

        <div class="notification-timing-grid">
            <label>
                Avisar quantos minutos antes
                <div class="input-with-suffix">
                    <input
                        type="number"
                        name="notification_before_minutes"
                        min="0"
                        max="120"
                        value="<?= (int)($user['notification_before_minutes'] ?? 10) ?>"
                    >
                    <span>min</span>
                </div>
            </label>

            <label>
                Considerar atrasado após
                <div class="input-with-suffix">
                    <input
                        type="number"
                        name="notification_after_minutes"
                        min="0"
                        max="120"
                        value="<?= (int)($user['notification_after_minutes'] ?? 5) ?>"
                    >
                    <span>min</span>
                </div>
            </label>
        </div>

        <div class="notification-events-grid">
            <?php
            $notificationOptions = [
                ['notify_entry_enabled', 'log-in', 'Entrada', 'Lembrar de registrar a entrada.'],
                ['notify_lunch_start_enabled', 'utensils', 'Início do almoço', 'Avisar quando o horário de almoço estiver próximo.'],
                ['notify_lunch_return_enabled', 'coffee', 'Volta do almoço', 'Avisar com base no horário real em que o almoço foi iniciado.'],
                ['notify_clock_out_enabled', 'log-out', 'Saída', 'Lembrar de finalizar o expediente.'],
            ];
            ?>

            <?php foreach ($notificationOptions as [$field, $icon, $label, $description]): ?>
                <label class="notification-event-card">
                    <span class="notification-event-icon">
                        <i data-lucide="<?= e($icon) ?>"></i>
                    </span>

                    <span>
                        <strong><?= e($label) ?></strong>
                        <small><?= e($description) ?></small>
                    </span>

                    <span class="switch">
                        <input
                            type="checkbox"
                            name="<?= e($field) ?>"
                            value="1"
                            <?= (int)($user[$field] ?? 1) === 1 ? 'checked' : '' ?>
                        >
                        <span class="switch-slider"></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="settings-save-bar">
        <span>
            <i data-lucide="info"></i>
            As alterações passam a valer assim que forem salvas.
        </span>
        <button class="primary-button settings-save-button" type="submit">
            <i data-lucide="save"></i>
            Salvar configurações
        </button>
    </div>
</form>
