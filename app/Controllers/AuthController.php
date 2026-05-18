<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/AuthService.php';

class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function showLogin(): void
    {
        if (AuthService::check()) {
            header('Location: /dashboard');
            exit;
        }
        require BASE_PATH . '/app/Views/auth/login.php';
    }

    public function handleLogin(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Érvénytelen kérés. Kérjük próbáld újra.';
            header('Location: /login');
            exit;
        }

        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Az e-mail cím és a jelszó megadása kötelező.';
            header('Location: /login');
            exit;
        }

        $attempts_key = 'login_attempts_' . md5($email);
        $attempts     = $_SESSION[$attempts_key] ?? 0;
        $last_attempt = $_SESSION[$attempts_key . '_time'] ?? 0;

        if ($attempts >= 5 && (time() - $last_attempt) < 900) {
            $_SESSION['error'] = 'Túl sok sikertelen próbálkozás. Várj 15 percet.';
            header('Location: /login');
            exit;
        }

        if ($this->auth->login($email, $password, $remember)) {
            unset($_SESSION[$attempts_key], $_SESSION[$attempts_key . '_time']);
            header('Location: /dashboard');
            exit;
        }

        $_SESSION[$attempts_key]           = $attempts + 1;
        $_SESSION[$attempts_key . '_time'] = time();
        $_SESSION['error'] = 'Hibás e-mail cím vagy jelszó.';
        header('Location: /login');
        exit;
    }

    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}
