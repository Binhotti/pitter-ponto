<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (authUser()) {
            redirect('dashboard');
        }

        $this->guestView('auth/login', [
            'title' => 'Entrar',
            'error' => flash('error'),
            'success' => flash('success'),
        ]);
    }

    public function showRegister(): void
    {
        if (authUser()) {
            redirect('dashboard');
        }

        $this->guestView('auth/register', [
            'title' => 'Criar conta',
            'error' => flash('error'),
        ]);
    }

    public function login(): void
    {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        $user = (new User())->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'E-mail ou senha inválidos.');
            redirect('login');
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        redirect('dashboard');
    }

    public function register(): void
    {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirmation = (string)($_POST['password_confirmation'] ?? '');

        if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Preencha nome e e-mail corretamente.');
            redirect('register');
        }

        if (strlen($password) < 6 || $password !== $confirmation) {
            flash('error', 'A senha deve ter ao menos 6 caracteres e a confirmação precisa ser igual.');
            redirect('register');
        }

        $users = new User();

        if ($users->findByEmail($email)) {
            flash('error', 'Este e-mail já está cadastrado.');
            redirect('register');
        }

        $users->create($name, $email, $password);

        flash('success', 'Conta criada. Agora você já pode entrar.');
        redirect('login');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        redirect('login');
    }
}
