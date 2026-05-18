<?php
// Környezeti változók betöltése
function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

return [
    'host'    => env('DB_HOST', 'localhost'),
    'dbname'  => env('DB_NAME'),
    'user'    => env('DB_USER'),
    'pass'    => env('DB_PASS'),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
];
