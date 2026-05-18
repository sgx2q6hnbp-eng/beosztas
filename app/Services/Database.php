<?php
declare(strict_types=1);

/**
 * Database – PDO singleton
 * Egyetlen kapcsolatot tart fenn az egész kérés során.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require BASE_PATH . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['dbname'],
                $cfg['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            } catch (PDOException $e) {
                // Éles környezetben NE írd ki a részletes hibát!
                error_log('DB kapcsolat hiba: ' . $e->getMessage());
                http_response_code(500);
                exit('Adatbázis kapcsolati hiba. Kérjük próbáld újra később.');
            }
        }

        return self::$instance;
    }
}
