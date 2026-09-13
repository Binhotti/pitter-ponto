<div class="page-heading admin-page-heading">
    <div>
        <a href="<?= url('admin-users') ?>" class="admin-back-link">← Usuários</a>
        <h1>Editar conta</h1>
        <p>Atualize os dados e permissões de <?= e($selectedUser['name']) ?>.</p>
    </div>
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

<div class="admin-edit-user-grid">
    <section class="panel admin-edit-user-card">
        <div class="panel-title-row">
            <div>
                <h3>Dados da conta</h3>
                <p>O e-mail precisa continuar sendo único.</p>
            </div>
        </div>

        <form method="post" action="<?= url('admin-user-update') ?>" class="admin-edit-user-form">
            <input type="hidden" name="user_id" value="<?= (int)$selectedUser['id'] ?>">

            <label>
                Nome completo
                <input
                    type="text"
                    name="name"
                    value="<?= e($selectedUser['name']) ?>"
                    minlength="3"
                    maxlength="120"
                    required
                >
            </label>

            <label>
                E-mail
                <input
                    type="email"
                    name="email"
                    value="<?= e($selectedUser['email']) ?>"
                    maxlength="160"
                    required
                >
            </label>

            <label>
                Perfil
                <select name="role" required>
                    <option
                        value="employee"
                        <?= $selectedUser['role'] === 'employee' ? 'selected' : '' ?>
                    >
                        Colaborador
                    </option>

                    <option
                        value="admin"
                        <?= $selectedUser['role'] === 'admin' ? 'selected' : '' ?>
                    >
                        Administrador
                    </option>
                </select>
            </label>

            <label>
                Nova senha
                <div class="password-input-wrap">
                    <input
                        type="password"
                        name="password"
                        placeholder="Deixe em branco para manter a senha atual"
                        minlength="8"
                        maxlength="128"
                        autocomplete="new-password"
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
                <small class="admin-form-help">
                    Se preenchida, precisa ter 8+ caracteres, uma letra e um número.
                </small>
            </label>

            <label class="admin-active-toggle">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?= (int)$selectedUser['is_active'] === 1 ? 'checked' : '' ?>
                >
                <span>
                    <strong>Conta ativa</strong>
                    <small>Contas inativas não conseguem entrar no sistema.</small>
                </span>
            </label>

            <div class="admin-edit-actions">
                <a href="<?= url('admin-users') ?>" class="secondary-button">
                    Cancelar
                </a>

                <button type="submit" class="primary-button">
                    <i data-lucide="save"></i>
                    Salvar alterações
                </button>
            </div>
        </form>
    </section>

    <aside class="panel admin-danger-zone">
        <span class="admin-danger-icon">
            <i data-lucide="triangle-alert"></i>
        </span>

        <div>
            <h3>Excluir conta</h3>
            <p>
                Excluir remove a conta e os dados relacionados que o banco
                permitir apagar em cascata. Esta ação não pode ser desfeita.
            </p>
        </div>

        <?php if ((int)$selectedUser['id'] === (int)authUser()['id']): ?>
            <div class="admin-delete-disabled">
                Você não pode excluir sua própria conta enquanto está conectado.
            </div>
        <?php else: ?>
            <form
                method="post"
                action="<?= url('admin-user-delete') ?>"
                onsubmit="return confirm('Tem certeza que deseja excluir a conta de <?= e(addslashes($selectedUser['name'])) ?>? Esta ação não pode ser desfeita.');"
            >
                <input type="hidden" name="user_id" value="<?= (int)$selectedUser['id'] ?>">

                <button type="submit" class="admin-delete-button">
                    <i data-lucide="trash-2"></i>
                    Excluir conta
                </button>
            </form>
        <?php endif; ?>
    </aside>
</div>

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
