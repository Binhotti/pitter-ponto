<?php
function adminUserMinutes(int $minutes): string
{
    return intdiv(max(0, $minutes), 60) . 'h '
        . str_pad((string)(max(0, $minutes) % 60), 2, '0', STR_PAD_LEFT)
        . 'min';
}

$statusLabels = [
    'working' => ['Trabalhando', 'green'],
    'lunch' => ['Em almoço', 'yellow'],
    'finished' => ['Finalizado', 'blue'],
    'not_started' => ['Não iniciou', 'gray'],
    'inactive' => ['Conta inativa', 'red'],
];
?>
<div class="page-heading admin-page-heading">
    <div>
        <a href="<?= url('admin') ?>" class="admin-back-link">← Administração</a>
        <h1>Usuários</h1>
        <p>Consulte contas, status atual e últimos acessos.</p>
    </div>
</div>

<?php if ($message = flash('error')): ?>
    <div class="alert error"><?= e($message) ?></div>
<?php endif; ?>

<section class="panel admin-users-panel">
    <div class="admin-users-table-wrap">
        <table class="admin-users-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Horas hoje</th>
                    <th>Último acesso</th>
                    <th>Cadastro</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $item = $row['user'];
                    $meta = $statusLabels[$row['status']['key']] ?? $statusLabels['not_started'];
                ?>
                    <tr>
                        <td>
                            <div class="admin-table-user">
                                <span class="admin-user-avatar small">
                                    <?php if (!empty($item['avatar_path'])): ?>
                                        <img src="<?= config('app.url') . '/' . e($item['avatar_path']) ?>" alt="">
                                    <?php else: ?>
                                        <?= e(mb_strtoupper(mb_substr($item['name'], 0, 1))) ?>
                                    <?php endif; ?>
                                </span>
                                <span>
                                    <strong><?= e($item['name']) ?></strong>
                                    <small><?= e($item['email']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="admin-role-badge <?= $item['role'] === 'admin' ? 'admin' : '' ?>">
                                <?= $item['role'] === 'admin' ? 'Administrador' : 'Colaborador' ?>
                            </span>
                        </td>
                        <td>
                            <span class="admin-status-pill <?= e($meta[1]) ?>"><?= e($meta[0]) ?></span>
                        </td>
                        <td><?= e(adminUserMinutes($row['workedToday'])) ?></td>
                        <td>
                            <?= $row['lastLogin']
                                ? date('d/m/Y H:i', strtotime($row['lastLogin']['logged_in_at']))
                                : 'Nunca' ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                        <td class="admin-table-action">
                            <a href="<?= url('admin-user') . '&id=' . (int)$item['id'] ?>">
                                Ver detalhes <i data-lucide="arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
