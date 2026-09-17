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
        <button class="sidebar-collapse-button" id="desktop-sidebar-toggle" type="button" aria-label="Recolher menu" title="Recolher menu">
            <i data-lucide="panel-left-close"></i>
        </button>
        <a href="<?= url('dashboard') ?>" class="brand">
            <img src="<?= asset('images/pitterpan-logo.png') ?>" alt="Pitter Pan Festas">
        </a>

        <nav class="nav-menu">
            <a class="nav-item <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" data-tooltip="Início" href="<?= url('dashboard') ?>">
                <i data-lucide="house" class="nav-icon"></i><span>Início</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'history' ? 'active' : '' ?>" data-tooltip="Histórico" href="<?= url('history') ?>">
                <i data-lucide="notebook-tabs" class="nav-icon"></i><span>Histórico</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'calendar' ? 'active' : '' ?>" data-tooltip="Calendário" href="<?= url('calendar') ?>">
                <i data-lucide="calendar-days" class="nav-icon"></i><span>Calendário</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'absences' ? 'active' : '' ?>" data-tooltip="Ausências e Justificativas" href="<?= url('absences') ?>">
                <i data-lucide="calendar-off" class="nav-icon"></i><span>Ausências e Justificativas</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'reports' ? 'active' : '' ?>" data-tooltip="Relatórios" href="<?= url('reports') ?>">
                <i data-lucide="chart-no-axes-column-increasing" class="nav-icon"></i><span>Relatórios</span>
            </a>



            <?php if (isAdmin()): ?>
                <div class="nav-admin-divider">
                    <span>ADMIN</span>
                </div>

                <a class="nav-item <?= ($active ?? '') === 'admin' ? 'active admin-active' : '' ?>" data-tooltip="Administração" href="<?= url('admin') ?>">
                    <i data-lucide="shield-check" class="nav-icon"></i><span>Administração</span>
                </a>
            <?php endif; ?>

            <a class="nav-item <?= ($active ?? '') === 'profile' ? 'active' : '' ?>" data-tooltip="Perfil" href="<?= url('profile') ?>">
                <i data-lucide="user-round" class="nav-icon"></i><span>Perfil</span>
            </a>

            <a class="nav-item <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" data-tooltip="Configurações" href="<?= url('settings') ?>">
                <i data-lucide="settings" class="nav-icon"></i><span>Configurações</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <a href="<?= url('profile') ?>" class="mini-profile" data-tooltip="Perfil">
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
                    <small><?= isAdmin() ? 'Administrador' : 'Colaborador' ?></small>
                </span>
                <i data-lucide="chevron-right" class="profile-chevron"></i>
            </a>

            <a class="logout-link" data-tooltip="Sair" href="<?= url('logout') ?>">
                <i data-lucide="log-out"></i><span>Sair</span>
            </a>
        </div>
    </aside>
    <button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Fechar menu"></button>

    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu" id="mobile-menu" type="button" aria-label="Abrir menu">
                <i data-lucide="menu"></i>
            </button>

            <form class="search-box global-search-form" method="GET" action="<?= config('app.url') ?>">
                <input type="hidden" name="route" value="search">
                <i data-lucide="search"></i>
                <input
                    type="search"
                    name="q"
                    value="<?= ($active ?? '') === 'search' ? e((string)($_GET['q'] ?? '')) : '' ?>"
                    placeholder="Buscar algo..."
                    aria-label="Buscar"
                    autocomplete="off"
                >
            </form>

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

    <nav class="mobile-bottom-nav" aria-label="Navegação principal no celular">
        <a href="<?= url('dashboard') ?>" class="mobile-bottom-item <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" data-mobile-nav="dashboard">
            <i data-lucide="house"></i><span>Início</span>
        </a>
        <a href="<?= url('history') ?>" class="mobile-bottom-item <?= ($active ?? '') === 'history' ? 'active' : '' ?>" data-mobile-nav="history">
            <i data-lucide="notebook-tabs"></i><span>Histórico</span>
        </a>
        <a href="<?= url('calendar') ?>" class="mobile-bottom-item <?= ($active ?? '') === 'calendar' ? 'active' : '' ?>" data-mobile-nav="calendar">
            <i data-lucide="calendar-days"></i><span>Calendário</span>
        </a>
        <a href="<?= url('reports') ?>" class="mobile-bottom-item <?= ($active ?? '') === 'reports' ? 'active' : '' ?>" data-mobile-nav="reports">
            <i data-lucide="chart-no-axes-column-increasing"></i><span>Relatórios</span>
        </a>
        <button type="button" class="mobile-bottom-item mobile-more-toggle <?= in_array(($active ?? ''), ['absences','profile','settings','search','admin'], true) ? 'active' : '' ?>" id="mobile-more-toggle" aria-label="Mais opções" aria-expanded="false">
            <i data-lucide="ellipsis"></i><span>Mais</span>
        </button>
    </nav>

    <div class="mobile-more-layer" id="mobile-more-layer" aria-hidden="true">
        <button class="mobile-more-backdrop" id="mobile-more-backdrop" type="button" aria-label="Fechar mais opções"></button>

        <section class="mobile-more-sheet" role="dialog" aria-modal="true" aria-labelledby="mobile-more-title">
            <div class="mobile-more-handle" aria-hidden="true"></div>

            <div class="mobile-more-head">
                <div>
                    <h2 id="mobile-more-title">Mais opções</h2>
                    <p>Acesse as outras áreas do Pitter Ponto.</p>
                </div>
                <button type="button" class="mobile-more-close" id="mobile-more-close" aria-label="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="mobile-more-profile">
                <?php if ($avatarPath): ?>
                    <img src="<?= config('app.url') . '/' . e($avatarPath) ?>" alt="Foto de perfil" class="mobile-more-avatar">
                <?php else: ?>
                    <span class="mobile-more-avatar mobile-more-avatar-fallback"><?= e($initials ?: 'U') ?></span>
                <?php endif; ?>
                <span>
                    <strong><?= e($currentUser['name'] ?? 'Usuário') ?></strong>
                    <small><?= e($currentUser['email'] ?? '') ?></small>
                </span>
            </div>

            <div class="mobile-more-grid">

                <?php if (isAdmin()): ?>
                    <a href="<?= url('admin') ?>" class="<?= ($active ?? '') === 'admin' ? 'active' : '' ?>">
                        <span><i data-lucide="shield-check"></i></span><strong>Administração</strong><small>Equipe, usuários e acessos</small>
                    </a>
                <?php endif; ?>
                <a href="<?= url('absences') ?>" class="<?= ($active ?? '') === 'absences' ? 'active' : '' ?>">
                    <span><i data-lucide="calendar-off"></i></span><strong>Ausências</strong><small>Justificativas, folgas e férias</small>
                </a>
                <a href="<?= url('profile') ?>" class="<?= ($active ?? '') === 'profile' ? 'active' : '' ?>">
                    <span><i data-lucide="user-round"></i></span><strong>Perfil</strong><small>Dados pessoais e foto</small>
                </a>
                <a href="<?= url('settings') ?>" class="<?= ($active ?? '') === 'settings' ? 'active' : '' ?>">
                    <span><i data-lucide="settings"></i></span><strong>Configurações</strong><small>Jornada, tema e notificações</small>
                </a>
                <a href="<?= url('search') ?>" class="<?= ($active ?? '') === 'search' ? 'active' : '' ?>">
                    <span><i data-lucide="search"></i></span><strong>Busca</strong><small>Encontre telas e registros</small>
                </a>
                <a href="<?= url('logout') ?>" class="danger">
                    <span><i data-lucide="log-out"></i></span><strong>Sair</strong><small>Encerrar sua sessão</small>
                </a>
            </div>
        </section>
    </div>

</div>

<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
