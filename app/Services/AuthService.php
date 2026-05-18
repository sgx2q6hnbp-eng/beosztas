<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/Database.php';

/**
 * AuthService – Bejelentkezés, kijelentkezés, session kezelés
 */
class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Bejelentkezési kísérlet.
     * Visszatér true-val siker esetén, false-szal hiba esetén.
     */
    public function login(string $email, string $password, bool $remember = false): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, password, role, is_active
             FROM users
             WHERE email = :email
             LIMIT 1"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Session rögzítés
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'], 'fleet_id' => $user['fleet_id'] ?? null];

        // Session ID újragenerálás (session fixation védelem)
        session_regenerate_id(true);

        // Utolsó bejelentkezés frissítése
        $upd = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $upd->execute([':id' => $user['id']]);

        // "Emlékezz rám" cookie (30 nap)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
            $upd2 = $this->db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
            $upd2->execute([':token' => hash('sha256', $token), ':id' => $user['id']]);
        }

        return true;
    }

    /**
     * Kijelentkezés – session és cookie törlése.
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        session_destroy();
    }

    /**
     * Bejelentkezett-e a felhasználó?
     */
    public static function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Admin-e a bejelentkezett felhasználó?
     */
    public static function isAdmin(): bool
    {
        return self::check() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    /**
     * Bejelentkezett user ID-ja.
     */
    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Védi az útvonalat: nem bejelentkezett user-t átirányít.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Védi az útvonalat: nem admin user-t átirányít.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Hozzáférés megtagadva.');
        }
    }
}
