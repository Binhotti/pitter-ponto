<div class="page-heading">
    <div><h1>Perfil</h1><p>Atualize seus dados pessoais e informações usadas nos cálculos.</p></div>
</div>

<?php if (!empty($success)): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>

<section class="panel form-panel">
    <form method="POST" action="<?= url('profile') ?>" class="settings-form">
        <div class="form-grid">
            <label>Nome completo<input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
            <label>E-mail<input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
            <label>Salário mensal (opcional)<input type="number" step="0.01" name="salary" value="<?= e((string)$user['salary']) ?>" placeholder="0,00"></label>
            <label>Jornada diária<input type="text" value="<?= intdiv((int)$user['daily_minutes'], 60) ?>h <?= (int)$user['daily_minutes'] % 60 ?>min" disabled></label>
            <label>Horas mensais<input type="text" value="<?= (int)$user['monthly_hours'] ?>h" disabled></label>
            <label>Perfil<input type="text" value="<?= $user['role'] === 'admin' ? 'Administrador' : 'Colaborador' ?>" disabled></label>
        </div>
        <button class="primary-button compact" type="submit">Salvar alterações</button>
    </form>
</section>
