<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use PDOException;

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
            'warning' => flash('warning'),
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
            'warning' => flash('warning'),
        ]);
    }

    public function login(): void
    {
        $email = $this->normalizeEmail((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $ip = $this->clientIp();

        $attemptModel = new LoginAttempt();
        $attemptModel->cleanup();

        $status = $attemptModel->status($email, $ip);

        if ($status['locked']) {
            $minutes = max(1, (int)ceil($status['remaining_seconds'] / 60));

            flash(
                'error',
                "Muitas tentativas de login. Tente novamente em aproximadamente {$minutes} minuto(s)."
            );
            redirect('login');
        }

        $user = (new User())->findByEmail($email);

        $validCredentials =
            $user
            && (int)($user['is_active'] ?? 1) === 1
            && password_verify($password, (string)$user['password']);

        if (!$validCredentials) {
            $failure = $attemptModel->recordFailure(
                $email,
                $ip,
                (int)config('security.max_login_attempts', 5),
                (int)config('security.login_window_minutes', 15),
                (int)config('security.login_lock_minutes', 5)
            );

            if ($failure['locked']) {
                $minutes = max(
                    1,
                    (int)ceil($failure['remaining_seconds'] / 60)
                );

                flash(
                    'error',
                    "Muitas tentativas de login. Tente novamente em aproximadamente {$minutes} minuto(s)."
                );
            } else {
                flash('error', 'E-mail ou senha inválidos.');
            }

            redirect('login');
        }

        $attemptModel->clear($email, $ip);

        // Evita session fixation reutilizando um ID de sessão anterior.
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        $_SESSION['login_at'] = time();
        $_SESSION['last_activity'] = time();

        redirect('dashboard');
    }

    public function register(): void
    {
        $name = $this->normalizeName((string)($_POST['name'] ?? ''));
        $email = $this->normalizeEmail((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmation = (string)($_POST['password_confirmation'] ?? '');

        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            flash('error', 'Informe um nome válido entre 3 e 120 caracteres.');
            redirect('register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Informe um endereço de e-mail válido.');
            redirect('register');
        }

        if (!$this->emailDomainExists($email)) {
            flash(
                'error',
                'O domínio deste e-mail não pôde ser validado. Confira o endereço informado.'
            );
            redirect('register');
        }

        $passwordError = $this->passwordValidationError($password);

        if ($passwordError !== null) {
            flash('error', $passwordError);
            redirect('register');
        }

        if ($password !== $confirmation) {
            flash('error', 'A confirmação da senha não corresponde à senha informada.');
            redirect('register');
        }

        $users = new User();

        if ($users->findByEmail($email)) {
            flash('error', 'Este e-mail já está cadastrado.');
            redirect('register');
        }

        $duplicateName = $users->nameExists($name);

        try {
            $users->create($name, $email, $password);
        } catch (PDOException $exception) {
            /*
             * O UNIQUE do banco é a última barreira contra duas
             * requisições simultâneas tentando cadastrar o mesmo e-mail.
             */
            if ($exception->getCode() === '23000') {
                flash('error', 'Este e-mail já está cadastrado.');
                redirect('register');
            }

            throw $exception;
        }

        if ($duplicateName) {
            flash(
                'warning',
                'Aviso: já existe outro cadastro com o mesmo nome. Isso é permitido; o e-mail continua sendo o identificador único.'
            );
        }

        flash('success', 'Conta criada com segurança. Agora você já pode entrar.');
        redirect('login');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        redirect('login');
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        return preg_replace('/\s+/u', ' ', $name) ?: $name;
    }

    private function passwordValidationError(string $password): ?string
    {
        $length = mb_strlen($password);

        if ($length < 8) {
            return 'A senha precisa ter pelo menos 8 caracteres.';
        }

        if ($length > 128) {
            return 'A senha pode ter no máximo 128 caracteres.';
        }

        if (!preg_match('/\p{L}/u', $password)) {
            return 'A senha precisa conter pelo menos uma letra.';
        }

        if (!preg_match('/\d/', $password)) {
            return 'A senha precisa conter pelo menos um número.';
        }

        return null;
    }

    private function emailDomainExists(string $email): bool
    {
        $atPosition = strrpos($email, '@');

        if ($atPosition === false) {
            return false;
        }

        $domain = substr($email, $atPosition + 1);

        if ($domain === '') {
            return false;
        }

        if (function_exists('idn_to_ascii')) {
            $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT);

            if (is_string($asciiDomain) && $asciiDomain !== '') {
                $domain = $asciiDomain;
            }
        }

        return checkdnsrr($domain, 'MX')
            || checkdnsrr($domain, 'A')
            || checkdnsrr($domain, 'AAAA');
    }

    private function clientIp(): string
    {
        /*
         * Não confiamos em X-Forwarded-For diretamente aqui.
         * Em XAMPP/local e hospedagem comum, REMOTE_ADDR é a origem segura.
         */
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        return mb_substr($ip !== '' ? $ip : 'unknown', 0, 45);
    }
}
