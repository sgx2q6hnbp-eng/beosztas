<?php
return [
    'name'             => $_ENV['APP_NAME']        ?? 'Munkabeosztás Rendszer',
    'url'              => $_ENV['APP_URL']          ?? 'http://localhost',
    'env'              => $_ENV['APP_ENV']          ?? 'production',
    'debug'            => $_ENV['APP_DEBUG']        ?? false,
    'session_name'     => $_ENV['SESSION_NAME']     ?? 'beosztás_sess',
    'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
    'mail_host'        => $_ENV['MAIL_HOST']        ?? 'smtp.hostinger.com',
    'mail_port'        => (int)($_ENV['MAIL_PORT']  ?? 587),
    'mail_username'    => $_ENV['MAIL_USERNAME']    ?? '',
    'mail_password'    => $_ENV['MAIL_PASSWORD']    ?? '',
    'mail_from_name'   => $_ENV['MAIL_FROM_NAME']   ?? 'Beosztás Rendszer',
    'mail_encryption'  => $_ENV['MAIL_ENCRYPTION']  ?? 'tls',
];
