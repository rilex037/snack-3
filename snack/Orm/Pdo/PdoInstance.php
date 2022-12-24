<?php

declare(strict_types=1);

namespace Snack\Orm;

use Snack\Env;
use Snack\Exception\SnackException;

$config = [
    'host' => Env::get('DB_HOST'),
    'dbname' => Env::get('DB_NAME'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASSWORD'),
];

try {
    return new \PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['username'],
        $config['password']
    );
} catch (\PDOException $e) {
    throw new SnackException('Cannot connect to database! ' . $e->getMessage());
}
