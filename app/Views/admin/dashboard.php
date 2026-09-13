<?php
function adminMinutes(int $minutes): string
{
    $minutes = max(0, $minutes);

    return intdiv($minutes, 60) . 'h '
        . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT)
        . 'min';
}

$statusMeta = [
    'working' => ['Trabalhando', 'green', 'briefcase-business'],
    'lunch' => ['Em almoço', 'yellow', 'utensils'],
    'finished' => ['Finalizado', 'blue', 'circle-check-big'],
    'not_started' => ['Não iniciou', 'gray', 'clock-3'],
    'inactive' => ['Inativo', 'red', 'user-x'],
];

$loginTotals = array_map(
    fn(array $item): int => (int)$item['total'],
    $loginChart
);
$maxLogin = max(1, ...$loginTotals);
?>
<div class="page-heading admin-page-heading">
    <div>
        <span class="admin-eyebrow">
            <i data-lucide="shield-check"></i>
            Área administrativa
        </span>
        <h1>Visão da equipe</h1>
        <p>Acompanhe usuários, jornada, acessos e pendências em um só lugar.</p>
    </div>

    <a class="primary-button admin-users-button" href="<?= url('admin-users') ?>">
        <i data-lucide="users-round"></i>
        Ver usuários
    </a>
</div>

<div class="admin-stats-grid">
    <article class="admin-stat-card">
        <span class="admin-stat-icon blue"><i data-lucide="users-round"></i></span>
        <div>
            <small>Usuários cadastrados</small>
            <strong><?= (int)$totalUsers ?></strong>
            <span><?= (int)$activeUsers ?> conta(s) ativa(s)</span>
        </div>
    </article>

    <article class="admin-stat-card">
        <span class="admin-stat-icon green"><i data-lucide="briefcase-business"></i></span>
        <div>
            <small>Trabalhando agora</small>
            <strong><?= (int)$working ?></strong>
            <span><?= (int)$lunch ?> em almoço</span>
        </div>
    </article>

    <article class="admin-stat-card">
        <span class="admin-stat-icon yellow"><i data-lucide="timer"></i></span>
        <div>
            <small>Horas da equipe hoje</small>
            <strong><?= e(adminMinutes($workedToday)) ?></strong>
            <span><?= e(adminMinutes($extraToday)) ?> de extras</span>
        </div>
    </article>

    <article class="admin-stat-card">
        <span class="admin-stat-icon purple"><i data-lucide="log-in"></i></span>
        <div>
            <small>Logins hoje</small>
            <strong><?= (int)$loginsToday ?></strong>
            <span><?= (int)$uniqueLoginsToday ?> usuário(s) diferente(s)</span>
        </div>
    </article>
</div>

<div class="admin-main-grid">
    <section class="panel admin-team-panel">
        <div class="panel-title-row">
            <div>
                <h3>Situação da equipe hoje</h3>
                <p>Status baseado nas marcações do dia.</p>
            </div>

            <div class="admin-status-summary">
                <span class="green"><?= (int)$working ?> trabalhando</span>
                <span class="yellow"><?= (int)$lunch ?> almoço</span>
                <span class="blue"><?= (int)$finished ?> finalizados</span>
                <span class="gray"><?= (int)$notStarted ?> não iniciaram</span>
            </div>
        </div>

        <div class="admin-team-list">
            <?php foreach ($team as $row):
                $meta = $statusMeta[$row['status']['key']] ?? $statusMeta['not_started'];
                $teamUser = $row['user'];
            ?>
                <a href="<?= url('admin-user') . '&id=' . (int)$teamUser['id'] ?>" class="admin-team-row">
                    <span class="admin-user-avatar">
                        <?php if (!empty($teamUser['avatar_path'])): ?>
                            <img src="<?= config('app.url') . '/' . e($teamUser['avatar_path']) ?>" alt="">
                        <?php else: ?>
                            <?= e(mb_strtoupper(mb_substr($teamUser['name'], 0, 1))) ?>
                        <?php endif; ?>
                    </span>

                    <span class="admin-team-person">
                        <strong><?= e($teamUser['name']) ?></strong>
                        <small>
                            <?= e($teamUser['email']) ?>
                            <?php if ($teamUser['role'] === 'admin'): ?> • Admin<?php endif; ?>
                        </small>
                    </span>

                    <span class="admin-team-clock">
                        <small>Entrada</small>
                        <strong><?= e($row['clockIn'] ?? '—') ?></strong>
                    </span>

                    <span class="admin-team-clock">
                        <small>Hoje</small>
                        <strong><?= e(adminMinutes($row['summary']['worked'])) ?></strong>
                    </span>

                    <span class="admin-status-pill <?= e($meta[1]) ?>">
                        <i data-lucide="<?= e($meta[2]) ?>"></i>
                        <?= e($meta[0]) ?>
                    </span>

                    <i data-lucide="chevron-right" class="admin-row-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel admin-login-chart-panel">
        <div class="panel-title-row">
            <div>
                <h3>Acessos nos últimos 7 dias</h3>
                <p>Quantidade de logins válidos registrados.</p>
            </div>
        </div>

        <div class="admin-login-chart">
            <?php foreach ($loginChart as $item):
                $height = $item['total'] > 0
                    ? max(12, ($item['total'] / $maxLogin) * 100)
                    : 3;
            ?>
                <div class="admin-login-bar-col">
                    <span><?= (int)$item['total'] ?></span>
                    <div class="admin-login-track">
                        <i style="height: <?= e(number_format($height, 2, '.', '')) ?>%"></i>
                    </div>
                    <strong><?= e($item['day']) ?></strong>
                    <small><?= e($item['label']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="admin-secondary-grid">
    <section class="panel">
        <div class="panel-title-row">
            <div>
                <h3>Pendências da equipe</h3>
                <p>Problemas encontrados nos últimos dias.</p>
            </div>
            <span class="admin-count-badge"><?= count($issues) ?></span>
        </div>

        <?php if ($issues === []): ?>
            <div class="admin-empty-state">
                <i data-lucide="circle-check-big"></i>
                <strong>Nenhuma pendência encontrada.</strong>
                <small>A equipe está com os registros em dia.</small>
            </div>
        <?php else: ?>
            <div class="admin-issue-list">
                <?php foreach ($issues as $issue): ?>
                    <a href="<?= url('admin-user') . '&id=' . (int)$issue['user_id'] ?>" class="admin-issue-row">
                        <span class="admin-issue-icon <?= e($issue['severity']) ?>">
                            <i data-lucide="<?= e($issue['icon']) ?>"></i>
                        </span>
                        <span>
                            <strong><?= e($issue['user_name']) ?></strong>
                            <small>
                                <?= date('d/m/Y', strtotime($issue['date'])) ?>
                                • <?= e($issue['title']) ?>
                            </small>
                        </span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-title-row">
            <div>
                <h3>Ausências de hoje</h3>
                <p>Folgas, férias, atestados e justificativas.</p>
            </div>
            <span class="admin-count-badge"><?= count($todayDayOffs) ?></span>
        </div>

        <?php if ($todayDayOffs === []): ?>
            <div class="admin-empty-state compact">
                <i data-lucide="calendar-check-2"></i>
                <strong>Nenhuma ausência cadastrada hoje.</strong>
            </div>
        <?php else: ?>
            <div class="admin-dayoff-list">
                <?php foreach ($todayDayOffs as $item): ?>
                    <a href="<?= url('admin-user') . '&id=' . (int)$item['user_id'] ?>" class="admin-dayoff-row">
                        <span><i data-lucide="calendar-off"></i></span>
                        <div>
                            <strong><?= e($item['name']) ?></strong>
                            <small><?= e(ucfirst(str_replace('_', ' ', $item['type']))) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-title-row">
            <div>
                <h3>Últimos acessos</h3>
                <p>Logins válidos mais recentes.</p>
            </div>
        </div>

        <div class="admin-login-list">
            <?php if ($recentLogins === []): ?>
                <div class="admin-empty-state compact">
                    <i data-lucide="log-in"></i>
                    <strong>Nenhum login registrado ainda.</strong>
                </div>
            <?php endif; ?>

            <?php foreach ($recentLogins as $login): ?>
                <a href="<?= url('admin-user') . '&id=' . (int)$login['user_id'] ?>" class="admin-login-row">
                    <span>
                        <strong><?= e($login['name']) ?></strong>
                        <small><?= date('d/m/Y H:i', strtotime($login['logged_in_at'])) ?></small>
                    </span>
                    <em><?= e($login['ip_address']) ?></em>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
