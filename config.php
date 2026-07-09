<?php

declare(strict_types=1);

use Snack\Env;

return [

    'app' => [
        'debug' => Env::get('APP_DEBUG', 'false') === 'true',
    ],

    'database' => [
        'driver' => Env::get('DB_DRIVER', 'mysql'),
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_NAME', 'snack'),
        'username' => Env::get('DB_USER', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],

    'view' => [
        'templates_path' => __DIR__ . '/app/templates',
        'public_path' => __DIR__ . '/public',
    ],

    'routes' => [
        __DIR__ . '/app/routes/web.php',
        __DIR__ . '/app/routes/api.php',
    ],

];
