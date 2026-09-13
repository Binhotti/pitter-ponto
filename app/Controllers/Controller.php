<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\NotificationService;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        if (authUser()) {
            $freshUser = (new User())->find((int)authUser()['id']);

            if ($freshUser) {
                $data['user'] ??= $freshUser;
                $data['smartNotifications'] ??= (new NotificationService())
                    ->forUser($freshUser);
            }
        }

        extract($data, EXTR_SKIP);
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        require BASE_PATH . '/app/Views/layouts/app.php';
    }

    protected function guestView(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';
        require BASE_PATH . '/app/Views/layouts/guest.php';
    }

    protected function requireAuth(): void
    {
        if (!authUser()) {
            redirect('login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();

        if (!isAdmin()) {
            flash('error', 'Acesso restrito ao administrador.');
            redirect('dashboard');
        }
    }
}
