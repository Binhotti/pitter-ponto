<?php
function adminDetailMinutes(int $minutes): string
{
    $sign = $minutes < 0 ? '-' : '';
    $minutes = abs($minutes);

    return $sign . intdiv($minutes, 60) . 'h '
        . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT)
        . 'min';
}

$prevMonth = (new DateTimeImmutable($month . '-01'))->modify('-1 month')->format('Y-m');
$nextMonth = (new DateTimeImmutable($month . '-01'))->modify('+1 month')->format('Y-m');

$statusLabels = [
    'working' => ['Trabalhando', 'green'],
    'lunch' => ['Em almoço', 'yellow'],
    'finished' => ['Expediente finalizado', 'blue'],
    'not_started' => ['Não iniciou hoje', 'gray'],
];
$statusMeta = $statusLabels[$status['key']] ?? $statusLabels['not_started'];
?>
<div class="page-heading admin-page-heading">
    <div>
        <a href="<?= url('admin-users') ?>" class="admin-back-link">← Usuários</a>
        <h1><?= e($selectedUser['name']) ?></h1>
        <p>Visão administrativa da jornada e dos acessos deste usuário.</p>
    </div>

    <span class="admin-status-pill <?= e($statusMeta[1]) ?>">
        <?= e($statusMeta[0]) ?>
    </span>
</div>

<section class="panel admin-user-hero">
    <div class="admin-user-hero-main">
        <span class="admin-user-avatar large">
            <?php if (!empty($selectedUser['avatar_path'])): ?>
                <img src="<?= config('app.url') . '/' . e($selectedUser['avatar_path']) ?>" alt="">
            <?php else: ?>
                <?= e(mb_strtoupper(mb_substr($selectedUser['name'], 0, 1))) ?>
            <?php endif; ?>
        </span>

        <div>
            <strong><?= e($selectedUser['name']) ?></strong>
            <span><?= e($selectedUser['email']) ?></span>
            <div class="admin-user-meta-badges">
                <em><?= $selectedUser['role'] === 'admin' ? 'Administrador' : 'Colaborador' ?></em>
                <em><?= (int)$selectedUser['is_active'] === 1 ? 'Conta ativa' : 'Conta inativa' ?></em>
            </div>
        </div>
    </div>

    <div class="admin-user-hero-meta">
        <div>
            <small>Jornada</small>
            <strong>
                <?= e(substr($selectedUser['workday_start'], 0, 5)) ?>
                – <?= e(substr($selectedUser['workday_end'], 0, 5)) ?>
            </strong>
        </div>
        <div>
            <small>Último acesso</small>
            <strong>
                <?= $lastLogin
                    ? date('d/m/Y H:i', strtotime($lastLogin['logged_in_at']))
                    : 'Nunca' ?>
            </strong>
        </div>
        <div>
            <small>Cadastrado em</small>
            <strong><?= date('d/m/Y', strtotime($selectedUser['created_at'])) ?></strong>
        </div>
    </div>
</section>

<section class="admin-user-month-nav">
    <a href="<?= url('admin-user') . '&id=' . (int)$selectedUser['id'] . '&month=' . e($prevMonth) ?>">← Mês anterior</a>
    <strong><?= date('m/Y', strtotime($month . '-01')) ?></strong>
    <a href="<?= url('admin-user') . '&id=' . (int)$selectedUser['id'] . '&month=' . e($nextMonth) ?>">Próximo mês →</a>
</section>

<div class="admin-user-summary-grid">
    <article><span>Trabalhado</span><strong><?= e(adminDetailMinutes($totals['worked'])) ?></strong></article>
    <article><span>Extra 50%</span><strong><?= e(adminDetailMinutes($totals['extra50'])) ?></strong></article>
    <article><span>Extra 100%</span><strong><?= e(adminDetailMinutes($totals['extra100'])) ?></strong></article>
    <article class="<?= $totals['bank'] >= 0 ? 'positive' : 'negative' ?>">
        <span>Banco</span><strong><?= $totals['bank'] > 0 ? '+' : '' ?><?= e(adminDetailMinutes($totals['bank'])) ?></strong>
    </article>
    <article><span>Ausências</span><strong><?= (int)$totals['absences'] ?></strong></article>
    <article><span>Dias finalizados</span><strong><?= (int)$totals['completed'] ?></strong></article>
</div>

<div class="admin-user-detail-grid">
    <section class="panel">
        <div class="panel-title-row">
            <div>
                <h3>Registros do mês</h3>
                <p>Dias com ponto, ausência, folga ou feriado.</p>
            </div>
        </div>

        <?php if ($days === []): ?>
            <div class="admin-empty-state">
                <i data-lucide="calendar-x-2"></i>
                <strong>Nenhum registro neste mês.</strong>
            </div>
        <?php else: ?>
            <div class="admin-user-days">
                <?php foreach ($days as $day): ?>
                    <div class="admin-user-day">
                        <div class="admin-user-day-date">
                            <strong><?= date('d/m', strtotime($day['date'])) ?></strong>
                            <small><?= date('D', strtotime($day['date'])) ?></small>
                        </div>

                        <div class="admin-user-day-times">
                            <?php foreach ($day['entries'] as $entry): ?>
                                <span>
                                    <small><?= match ($entry['entry_type']) {
                                        'clock_in' => 'Entrada',
                                        'lunch_start' => 'Almoço',
                                        'lunch_end' => 'Retorno',
                                        'clock_out' => 'Saída',
                                        default => 'Registro',
                                    } ?></small>
                                    <strong><?= date('H:i', strtotime($entry['recorded_at'])) ?></strong>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="admin-user-day-total">
                            <small>Trabalhado</small>
                            <strong><?= e(adminDetailMinutes($day['summary']['worked'])) ?></strong>
                        </div>

                        <?php if ($day['summary']['absence']): ?>
                            <span class="admin-status-pill red">Ausência</span>
                        <?php elseif ($day['dayOff']): ?>
                            <span class="admin-status-pill blue">
                                <?= e(ucfirst(str_replace('_', ' ', $day['dayOff']['type']))) ?>
                            </span>
                        <?php elseif ($day['summary']['completed']): ?>
                            <span class="admin-status-pill green">Finalizado</span>
                        <?php else: ?>
                            <span class="admin-status-pill yellow">Incompleto</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="admin-user-side-stack">
        <section class="panel">
            <div class="panel-title-row">
                <div>
                    <h3>Pendências</h3>
                    <p>Últimos 30 dias.</p>
                </div>
                <span class="admin-count-badge"><?= count($issues) ?></span>
            </div>

            <?php if ($issues === []): ?>
                <div class="admin-empty-state compact">
                    <i data-lucide="circle-check-big"></i>
                    <strong>Nenhuma pendência.</strong>
                </div>
            <?php else: ?>
                <div class="admin-issue-list">
                    <?php foreach (array_slice($issues, 0, 6) as $issue): ?>
                        <div class="admin-issue-row static">
                            <span class="admin-issue-icon <?= e($issue['severity']) ?>">
                                <i data-lucide="<?= e($issue['icon']) ?>"></i>
                            </span>
                            <span>
                                <strong><?= e($issue['title']) ?></strong>
                                <small><?= date('d/m/Y', strtotime($issue['date'])) ?> • <?= e($issue['message']) ?></small>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-title-row">
                <div>
                    <h3>Últimos acessos</h3>
                    <p>Histórico de login.</p>
                </div>
            </div>

            <div class="admin-login-list">
                <?php if ($recentLogins === []): ?>
                    <div class="admin-empty-state compact">
                        <strong>Nenhum login registrado.</strong>
                    </div>
                <?php endif; ?>

                <?php foreach ($recentLogins as $login): ?>
                    <div class="admin-login-row static">
                        <span>
                            <strong><?= date('d/m/Y H:i', strtotime($login['logged_in_at'])) ?></strong>
                            <small><?= e($login['ip_address']) ?></small>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
