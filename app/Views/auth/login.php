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

<?php if (!empty($warning)): ?>
    <div class="alert warning"><?= e($warning) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('login') ?>" class="auth-form">
    <label>
        E-mail
        <input type="email" name="email" placeholder="voce@empresa.com" autocomplete="email" maxlength="160" required>
    </label>

    <label>
        Senha
        <div class="password-input-wrap">
            <input
                type="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                maxlength="128"
                required
            >

            <button
                type="button"
                class="password-visibility-toggle"
                data-password-toggle
                aria-label="Mostrar senha"
                title="Mostrar senha"
            >
                <svg class="password-eye password-eye-show" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2.06 12.35C3.52 8.92 7.15 6.5 12 6.5s8.48 2.42 9.94 5.85a1 1 0 0 1 0 .8C20.48 16.58 16.85 19 12 19s-8.48-2.42-9.94-5.85a1 1 0 0 1 0-.8Z"></path>
                    <circle cx="12" cy="12.75" r="3"></circle>
                </svg>

                <svg class="password-eye password-eye-hide" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 3l18 18"></path>
                    <path d="M10.58 10.59a2 2 0 0 0 2.83 2.83"></path>
                    <path d="M9.88 5.08A10.7 10.7 0 0 1 12 4.87c4.85 0 8.48 2.42 9.94 5.85a1 1 0 0 1 0 .8 10.1 10.1 0 0 1-2.01 3.05"></path>
                    <path d="M6.61 6.61A10.15 10.15 0 0 0 2.06 10.72a1 1 0 0 0 0 .8C3.52 14.95 7.15 17.37 12 17.37c1.03 0 2-.11 2.9-.33"></path>
                </svg>
            </button>
        </div>
    </label>

    <button class="primary-button" type="submit">Entrar</button>
</form>


<p class="auth-switch">Ainda não tem conta? <a href="<?= url('register') ?>">Criar conta</a></p>


<script>
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const wrapper = button.closest('.password-input-wrap');
        const input = wrapper?.querySelector('input[type="password"], input[type="text"]');

        if (!input) {
            return;
        }

        const willShow = input.type === 'password';

        input.type = willShow ? 'text' : 'password';
        button.classList.toggle('showing', willShow);
        button.setAttribute('aria-label', willShow ? 'Ocultar senha' : 'Mostrar senha');
        button.setAttribute('title', willShow ? 'Ocultar senha' : 'Mostrar senha');
    });
});
</script>
