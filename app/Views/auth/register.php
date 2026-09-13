<div class="auth-header">
    <span class="eyebrow">Novo colaborador</span>
    <h2>Criar sua conta</h2>
    <p>Depois do cadastro, seu ponto já estará pronto para uso.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('register') ?>" class="auth-form">
    <label>
        Nome completo
        <input type="text" name="name" placeholder="Seu nome" required>
    </label>

    <label>
        E-mail
        <input type="email" name="email" placeholder="voce@empresa.com" required>
    </label>

    <label>
        Senha
        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
    </label>

    <label>
        Confirmar senha
        <input type="password" name="password_confirmation" placeholder="Repita a senha" required>
    </label>

    <button class="primary-button" type="submit">Criar conta</button>
</form>

<p class="auth-switch">Já tem conta? <a href="<?= url('login') ?>">Entrar</a></p>
