<?php
$avatarPath = $user['avatar_path'] ?? null;

$initials = '';
foreach (explode(' ', trim((string)$user['name'])) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    if (mb_strlen($initials) >= 2) {
        break;
    }
}
?>

<div class="page-heading">
    <div>
        <h1>Perfil</h1>
        <p>Atualize seus dados pessoais e sua foto de perfil.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($message = flash('error')): ?>
    <div class="alert error"><?= e($message) ?></div>
<?php endif; ?>

<?php if ($message = flash('warning')): ?>
    <div class="alert warning"><?= e($message) ?></div>
<?php endif; ?>

<form
    method="POST"
    action="<?= url('profile') ?>"
    enctype="multipart/form-data"
    class="profile-page-form"
>
    <section class="panel profile-main-card">
        <div class="profile-card-head">
            <span class="settings-section-icon red-soft-icon">
                <i data-lucide="user-round"></i>
            </span>

            <div>
                <h2>Perfil</h2>
                <p>Atualize seus dados e escolha uma foto de perfil.</p>
            </div>
        </div>

        <div class="profile-edit-layout">
            <div class="profile-avatar-column">
                <?php if ($avatarPath): ?>
                    <img
                        src="<?= config('app.url') . '/' . e($avatarPath) ?>"
                        alt="Foto de perfil"
                        class="profile-large-avatar"
                    >
                <?php else: ?>
                    <span class="profile-large-avatar profile-avatar-fallback">
                        <?= e($initials ?: 'U') ?>
                    </span>
                <?php endif; ?>

                <label class="avatar-upload-button">
                    <i data-lucide="camera"></i>
                    Escolher foto
                    <input
                        type="file"
                        name="avatar"
                        accept="image/jpeg,image/png,image/webp"
                        hidden
                    >
                </label>

                <?php if ($avatarPath): ?>
                    <label class="remove-avatar-option">
                        <input type="checkbox" name="remove_avatar" value="1">
                        Remover foto atual
                    </label>
                <?php endif; ?>

                <small>JPG, PNG ou WEBP • máximo 3 MB</small>
            </div>

            <div class="profile-fields-grid">
                <label>
                    Nome
                    <input
                        type="text"
                        name="name"
                        value="<?= e($user['name']) ?>"
                        required
                    >
                </label>

                <label>
                    E-mail
                    <input
                        type="email"
                        name="email"
                        value="<?= e($user['email']) ?>"
                        required
                    >
                </label>

                <label>
                    Salário mensal (opcional)
                    <input
                        type="number"
                        step="0.01"
                        name="salary"
                        value="<?= e((string)$user['salary']) ?>"
                        placeholder="0,00"
                    >
                </label>

                <label>
                    Jornada diária
                    <input
                        type="text"
                        value="<?= intdiv((int)$user['daily_minutes'], 60) ?>h <?= str_pad((string)((int)$user['daily_minutes'] % 60), 2, '0', STR_PAD_LEFT) ?>min"
                        disabled
                    >
                </label>

                <label>
                    Horas mensais
                    <input
                        type="text"
                        value="<?= (int)$user['monthly_hours'] ?>h"
                        disabled
                    >
                </label>


                <label>
                    Valor estimado da hora
                    <input
                        type="text"
                        value="<?php
                            $profileSalary = (float)($user['salary'] ?? 0);
                            $profileMonthlyHours = (int)($user['monthly_hours'] ?? 0);

                            echo ($profileSalary > 0 && $profileMonthlyHours > 0)
                                ? e('R$ ' . number_format($profileSalary / $profileMonthlyHours, 2, ',', '.'))
                                : 'Informe o salário';
                        ?>"
                        disabled
                    >
                </label>
            </div>
        </div>

        <div class="profile-actions-row">
            <button class="primary-button profile-save-button" type="submit">
                <i data-lucide="save"></i>
                Salvar alterações
            </button>
        </div>
    </section>
</form>
