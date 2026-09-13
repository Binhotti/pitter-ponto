<div class="auth-header">
    <span class="eyebrow">Novo colaborador</span>
    <h2>Criar sua conta</h2>
    <p>Depois do cadastro, seu ponto já estará pronto para uso.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($warning)): ?>
    <div class="alert warning"><?= e($warning) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('register') ?>" class="auth-form">
    <label>
        Nome completo
        <input type="text" name="name" placeholder="Seu nome" autocomplete="name" minlength="3" maxlength="120" required>
    </label>

    <label>
        E-mail
        <input type="email" name="email" placeholder="voce@empresa.com" autocomplete="email" maxlength="160" required>
    </label>

    <label>
        Senha
        <input type="password" name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" minlength="8" maxlength="128" required>
    </label>

    <label>
        Confirmar senha
        <input type="password" name="password_confirmation" placeholder="Repita a senha" autocomplete="new-password" minlength="8" maxlength="128" required>
    </label>

    <div class="auth-password-rules">
        <i data-lucide="shield-check"></i>
        <span>A senha deve ter pelo menos 8 caracteres, uma letra e um número.</span>
    </div>

    <button class="primary-button" type="submit">Criar conta</button>
</form>

<p class="auth-switch">Já tem conta? <a href="<?= url('login') ?>">Entrar</a></p>
