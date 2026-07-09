<?php

declare(strict_types=1);

namespace Snack\Database;

use PDO;
use PDOException;
use Snack\Exception\SnackException;

final class ConnectionFactory
{
    public static function make(array $config): Connection
    {
        $driver = $config['driver'] ?? 'mysql';

        try {
            $pdo = match ($driver) {
                'sqlite' => new PDO('sqlite:' . $config['database']),
                'mysql' => new PDO(
                    self::mysqlDsn($config),
                    $config['username'] ?? null,
                    $config['password'] ?? null
                ),
                default => throw new SnackException("Unsupported database driver [{$driver}]."),
            };
        } catch (PDOException $exception) {
            throw new SnackException('Cannot connect to database: ' . $exception->getMessage(), 0, $exception);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        foreach ($config['options'] ?? [] as $attribute => $value) {
            $pdo->setAttribute($attribute, $value);
        }

        return new Connection($pdo, $driver);
    }

    private static function mysqlDsn(array $config): string
    {
        $charset = $config['charset'] ?? 'utf8mb4';

        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '3306',
            $config['database'],
            $charset
        );
    }
}
