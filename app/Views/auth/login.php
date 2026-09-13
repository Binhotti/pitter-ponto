<div class="auth-header">
    <span class="eyebrow">Bem-vindo de volta</span>
    <h2>Entrar na sua conta</h2>
    <p>Use seu e-mail e senha para acessar o painel.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('login') ?>" class="auth-form">
    <label>
        E-mail
        <input type="email" name="email" placeholder="voce@empresa.com" required>
    </label>

    <label>
        Senha
        <input type="password" name="password" placeholder="••••••••" required>
    </label>

    <button class="primary-button" type="submit">Entrar</button>
</form>


<p class="auth-switch">Ainda não tem conta? <a href="<?= url('register') ?>">Criar conta</a></p>
