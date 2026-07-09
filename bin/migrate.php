#!/usr/bin/env php
<?php

declare(strict_types=1);

use Snack\Config\Repository;
use Snack\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config.php';
$repository = new Repository($config);
$connection = ConnectionFactory::make($repository->get('database', []));

$driver = (string) $repository->get('database.driver', 'mysql');
$directory = dirname(__DIR__) . "/database/migrations/{$driver}";

$connection->statement(
    'CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) PRIMARY KEY)'
);

$applied = array_column($connection->select('SELECT migration FROM migrations'), 'migration');

$files = glob($directory . '/*.sql') ?: [];
sort($files);

$ran = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        continue;
    }

    $sql = (string) file_get_contents($file);

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        $connection->statement($statement);
    }

    $connection->insert('INSERT INTO migrations (migration) VALUES (?)', [$name]);
    echo "Migrated: {$name}\n";
    $ran++;
}

echo $ran === 0 ? "Nothing to migrate.\n" : "Done ({$ran} migration(s)).\n";
