<?php
$currentUser = authUser();
$viewUser = $user ?? null;
$avatarPath = $viewUser['avatar_path'] ?? null;
$theme = $viewUser['theme'] ?? 'light';
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
<body class="<?= $theme === 'dark' ? 'theme-dark' : 'theme-light' ?>">
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

            <a class="nav-item <?= ($active ?? '') === 'absences' ? 'active' : '' ?>" href="<?= url('absences') ?>">
                <i data-lucide="calendar-off" class="nav-icon"></i><span>Ausências e Justificativas</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'reports' ? 'active' : '' ?>" href="<?= url('reports') ?>">
                <i data-lucide="chart-no-axes-column-increasing" class="nav-icon"></i><span>Relatórios</span>
            </a>


            <a class="nav-item <?= ($active ?? '') === 'profile' ? 'active' : '' ?>" href="<?= url('profile') ?>">
                <i data-lucide="user-round" class="nav-icon"></i><span>Perfil</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="<?= url('settings') ?>">
                <i data-lucide="settings" class="nav-icon"></i><span>Configurações</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <a href="<?= url('profile') ?>" class="mini-profile">
                <?php if ($avatarPath): ?>
                    <img
                        src="<?= config('app.url') . '/' . e($avatarPath) ?>"
                        alt="Foto de perfil"
                        class="avatar avatar-image"
                    >
                <?php else: ?>
                    <span class="avatar"><?= e($initials ?: 'U') ?></span>
                <?php endif; ?>
                <span class="mini-profile-text">
                    <strong><?= e($currentUser['name'] ?? 'Usuário') ?></strong>
                    <small>Colaborador</small>
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
                <div class="notification-center">
                    <button
                        class="icon-button notification-toggle"
                        type="button"
                        title="Notificações"
                        aria-label="Notificações"
                        aria-expanded="false"
                        data-notifications-enabled="<?= (int)($viewUser['notifications_enabled'] ?? 1) ?>"
                        data-browser-notifications-enabled="<?= (int)($viewUser['browser_notifications'] ?? 0) ?>"
                    >
                        <i data-lucide="bell"></i>

                        <?php if (!empty($smartNotifications)): ?>
                            <span class="notification-count"><?= count($smartNotifications) ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="notification-dropdown" aria-hidden="true">
                        <div class="notification-dropdown-head">
                            <strong>Notificações</strong>
                            <small><?= !empty($smartNotifications) ? count($smartNotifications) . ' pendente(s)' : 'Tudo em dia' ?></small>
                        </div>

                        <?php if (!empty($smartNotifications)): ?>
                            <div class="notification-dropdown-list">
                                <?php foreach ($smartNotifications as $notification): ?>
                                    <a
                                        href="<?= e($notification['action']) ?>"
                                        class="notification-dropdown-item"
                                        data-smart-notification
                                        data-notification-key="<?= e($notification['key']) ?>"
                                        data-notification-title="<?= e($notification['title']) ?>"
                                        data-notification-message="<?= e($notification['message']) ?>"
                                    >
                                        <span class="notification-dropdown-icon <?= e($notification['type']) ?>">
                                            <i data-lucide="<?= e($notification['icon']) ?>"></i>
                                        </span>
                                        <span>
                                            <strong><?= e($notification['title']) ?></strong>
                                            <small><?= e($notification['message']) ?></small>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="notification-empty">
                                <i data-lucide="circle-check-big"></i>
                                <span>Nenhum lembrete pendente.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

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
