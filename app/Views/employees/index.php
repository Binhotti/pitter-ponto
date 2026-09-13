<div class="page-heading">
    <div><h1>Funcionários</h1><p>Visão rápida das pessoas cadastradas no sistema.</p></div>
</div>

<section class="panel table-panel">
    <div class="responsive-table">
        <table>
            <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Jornada</th><th>Cadastrado em</th></tr></thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><strong><?= e($employee['name']) ?></strong></td>
                    <td><?= e($employee['email']) ?></td>
                    <td><span class="role-badge"><?= $employee['role'] === 'admin' ? 'Admin' : 'Colaborador' ?></span></td>
                    <td><?= intdiv((int)$employee['daily_minutes'], 60) ?>h <?= (int)$employee['daily_minutes'] % 60 ?>min</td>
                    <td><?= date('d/m/Y', strtotime($employee['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
