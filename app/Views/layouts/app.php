<?php
$currentUser = authUser();
$initials = '';

foreach (explode(' ', trim((string)($currentUser['name'] ?? 'U'))) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    if (mb_strlen($initials) >= 2) {
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> • Pitter Ponto</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="<?= url('dashboard') ?>" class="brand">
            <img src="<?= asset('images/pitterpan-logo.png') ?>" alt="Pitter Pan Festas">
        </a>

        <nav class="nav-menu">
            <a class="nav-item <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= url('dashboard') ?>">
                <i data-lucide="house" class="nav-icon"></i><span>Dashboard</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'point' ? 'active' : '' ?>" href="<?= url('dashboard') ?>#meu-ponto">
                <i data-lucide="clock-3" class="nav-icon"></i><span>Meu Ponto</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'history' ? 'active' : '' ?>" href="<?= url('history') ?>">
                <i data-lucide="notebook-tabs" class="nav-icon"></i><span>Histórico</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'calendar' ? 'active' : '' ?>" href="<?= url('calendar') ?>">
                <i data-lucide="calendar-days" class="nav-icon"></i><span>Calendário</span>
            </a>

            <a class="nav-item" href="<?= url('history') ?>">
                <i data-lucide="chart-no-axes-column-increasing" class="nav-icon"></i><span>Relatórios</span>
            </a>

            <?php if (isAdmin()): ?>
                <a class="nav-item <?= ($active ?? '') === 'employees' ? 'active' : '' ?>" href="<?= url('employees') ?>">
                    <i data-lucide="users" class="nav-icon"></i><span>Funcionários</span>
                </a>
            <?php endif; ?>

            <a class="nav-item <?= ($active ?? '') === 'profile' ? 'active' : '' ?>" href="<?= url('profile') ?>">
                <i data-lucide="user-round" class="nav-icon"></i><span>Perfil</span>
            </a>

            <?php if (isAdmin()): ?>
                <a class="nav-item <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="<?= url('settings') ?>">
                    <i data-lucide="settings" class="nav-icon"></i><span>Configurações</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= url('profile') ?>" class="mini-profile">
                <span class="avatar"><?= e($initials ?: 'U') ?></span>
                <span class="mini-profile-text">
                    <strong><?= e($currentUser['name'] ?? 'Usuário') ?></strong>
                    <small><?= isAdmin() ? 'Administrador' : 'Colaborador' ?></small>
                </span>
                <i data-lucide="chevron-right" class="profile-chevron"></i>
            </a>

            <a class="logout-link" href="<?= url('logout') ?>">
                <i data-lucide="log-out"></i><span>Sair</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu" id="mobile-menu" type="button" aria-label="Abrir menu">
                <i data-lucide="menu"></i>
            </button>

            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="search" placeholder="Buscar algo..." aria-label="Buscar">
            </div>

            <div class="topbar-right">
                <button class="icon-button" type="button" title="Notificações" aria-label="Notificações">
                    <i data-lucide="bell"></i>
                </button>

                <div class="company-name">
                    <strong>Pitter Pan Festas</strong>
                    <small>Controle de jornada</small>
                </div>
            </div>
        </header>

        <section class="page-content">
            <?php require $viewFile; ?>
        </section>
    </main>
</div>

<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
