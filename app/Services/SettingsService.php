<?php
declare(strict_types=1);

class SettingsService
{
    private static ?array $cache = null;

    private static function load(): void
    {
        if (self::$cache !== null) return;
        $db   = Database::getInstance();
        $rows = $db->query("SELECT key_name, value FROM settings")->fetchAll();
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['key_name']] = $row['value'];
        }
    }

    public static function get(string $key, string $default = '0'): string
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function isEnabled(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function set(string $key, string $value): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE settings SET value = :val WHERE key_name = :key");
        $stmt->execute([':val' => $value, ':key' => $key]);
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM settings ORDER BY id")->fetchAll();
    }

    /**
     * E-mail küldés engedélyezett-e egy adott típushoz?
     * Globális kapcsoló ÉS típusonkénti kapcsoló is be kell legyen.
     */
    public static function mailAllowed(string $type): bool
    {
        return self::isEnabled('mail_enabled') && self::isEnabled($type);
    }
}
