<?php
declare(strict_types=1);

define('BASE_PATH', '/home/u659067600/domains/darkgoldenrod-porpoise-233030.hostingersite.com');

require_once BASE_PATH . '/vendor/autoload.php';

$envFile = BASE_PATH . '/config/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \"'");
        putenv(trim($key) . '=' . trim($value, " \"'"));
    }
}

$appConfig = require BASE_PATH . '/config/app.php';

session_name($appConfig['session_name'] ?? 'beosztás_sess');
session_set_cookie_params([
    'lifetime' => $appConfig['session_lifetime'] ?? 7200,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

spl_autoload_register(function (string $class): void {
    $paths = [
        BASE_PATH . '/app/Controllers/' . $class . '.php',
        BASE_PATH . '/app/Models/'      . $class . '.php',
        BASE_PATH . '/app/Services/'    . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET'  => [
        '/'                      => fn() => header('Location: /login'),
        '/login'                 => fn() => (new AuthController())->showLogin(),
        '/logout'                => fn() => (new AuthController())->logout(),
        '/dashboard'             => fn() => (new DashboardController())->index(),
        '/schedule'              => fn() => (new ScheduleController())->index(),
        '/admin/import'          => fn() => (new ImportController())->showForm(),
        '/admin/import/template' => fn() => (new ImportController())->downloadTemplate(),
        '/admin/employees'       => fn() => (new AdminController())->employees(),
        '/admin/leaves'          => fn() => (new AdminController())->leaves(),
        '/admin/leaves/review'   => fn() => (new AdminController())->reviewLeave(),
        '/admin/leaves/store'    => fn() => (new AdminController())->adminStoreLeave(),
        '/leave'                 => fn() => (new LeaveController())->index(),
        '/leave/store'           => fn() => (new LeaveController())->store(),
        '/swap'                  => fn() => (new SwapController())->index(),
        '/swap/store'            => fn() => (new SwapController())->store(),
        '/schedule/overtime'     => fn() => (new ScheduleController())->toggleOvertime(),
        '/schedule/add'          => fn() => (new ScheduleController())->addShift(),
        '/schedule/delete'       => fn() => (new ScheduleController())->deleteShift(),
        '/swap/accept'           => fn() => (new SwapController())->accept(),
        '/swap/decline'          => fn() => (new SwapController())->decline(),
        '/admin/swaps'           => fn() => (new AdminSwapController())->index(),
        '/admin/swaps/approve'   => fn() => (new AdminSwapController())->approve(),
        '/admin/swaps/reject'    => fn() => (new AdminSwapController())->reject(),
        '/admin/logs'            => fn() => (new AdminController())->logs(),
        '/admin/settings'        => fn() => (new AdminController())->settings(),
    ],
    'POST' => [
        '/login'        => fn() => (new AuthController())->handleLogin(),
        '/admin/import' => fn() => (new ImportController())->handle(),
        '/admin/employees/store'  => fn() => (new AdminController())->storeEmployee(),
        '/admin/employees/update' => fn() => (new AdminController())->updateEmployee(),
        '/admin/employees/delete' => fn() => (new AdminController())->deleteEmployee(),
        '/admin/leaves/review'   => fn() => (new AdminController())->reviewLeave(),
        '/admin/leaves/store'    => fn() => (new AdminController())->adminStoreLeave(),
        '/admin/swaps/approve'   => fn() => (new AdminSwapController())->approve(),
        '/admin/swaps/reject'    => fn() => (new AdminSwapController())->reject(),
        '/leave/store'           => fn() => (new LeaveController())->store(),
        '/swap/store'            => fn() => (new SwapController())->store(),
        '/schedule/overtime'     => fn() => (new ScheduleController())->toggleOvertime(),
        '/swap/accept'           => fn() => (new SwapController())->accept(),
        '/swap/decline'          => fn() => (new SwapController())->decline(),
        '/admin/settings'        => fn() => (new AdminController())->saveSettings(),
        '/schedule/add'          => fn() => (new ScheduleController())->addShift(),
        '/schedule/delete'       => fn() => (new ScheduleController())->deleteShift(),
    ],
];

$handler = $routes[$requestMethod][$requestUri] ?? null;

if ($handler) {
    $handler();
} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="hu"><head><meta charset="UTF-8"><title>404</title>
    <style>body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;
    min-height:100vh;margin:0;background:#0F172A;color:#CBD5E1;}
    .box{text-align:center;}h1{font-size:4rem;color:#3B82F6;margin:0;}a{color:#3B82F6;}</style>
    </head><body><div class="box"><h1>404</h1><p>Az oldal nem talalhato.</p>
    <a href="/login">Vissza a bejelentkezeshez</a></div></body></html>';
}
