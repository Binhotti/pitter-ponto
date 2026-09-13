<?php
$workdayStart = substr((string)($user['workday_start'] ?? '08:00:00'), 0, 5);
$workdayEnd = substr((string)($user['workday_end'] ?? '17:48:00'), 0, 5);
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

        <div class="settings-grid three">
            <label>
                Horário padrão de entrada
                <input type="time" name="workday_start" value="<?= e($workdayStart) ?>" required>
            </label>

            <label>
                Horário padrão de saída
                <input type="time" name="workday_end" value="<?= e($workdayEnd) ?>" required>
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
                    <small>Exibe lembretes e alertas relacionados ao ponto.</small>
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
                    <small>Permite avisos enquanto o Pitter Ponto estiver aberto.</small>
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
