<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/Services/Database.php';

/**
 * User model – Felhasználók lekérdezése és kezelése
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** ID alapján egy felhasználó lekérése */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, role, fleet_id, phone, is_active, last_login
             FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Összes aktív dolgozó listázása (admin nézethez) */
    public function getAllActive(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id, u.name, u.email, u.role, u.fleet_id, f.name AS fleet_name, f.color
             FROM users u
             LEFT JOIN fleets f ON f.id = u.fleet_id
             WHERE u.is_active = 1
             ORDER BY u.fleet_id ASC, u.name ASC"
        );
        return $stmt->fetchAll();
    }

    /** Flotta alapján dolgozók lekérése */
    public function getByFleet(int $fleetId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, phone
             FROM users
             WHERE fleet_id = :fleet_id AND is_active = 1
             ORDER BY name ASC"
        );
        $stmt->execute([':fleet_id' => $fleetId]);
        return $stmt->fetchAll();
    }

    /** Új jelszó hash-elése és mentése */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "UPDATE users SET password = :password WHERE id = :id"
        );
        return $stmt->execute([':password' => $hash, ':id' => $userId]);
    }

    /** CSRF token generalasa es session-be mentese */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /** CSRF token ellenorzese */
    public static function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}
