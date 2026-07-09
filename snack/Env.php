<?php

declare(strict_types=1);

namespace Snack;

use Dotenv\Dotenv;

final class Env
{
    private static ?Dotenv $dotenv = null;

    private function __construct()
    {
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::boot();

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }

    public static function has(string $key): bool
    {
        self::boot();

        return isset($_ENV[$key]) || isset($_SERVER[$key]) || getenv($key) !== false;
    }

    private static function boot(): void
    {
        if (self::$dotenv !== null) {
            return;
        }

        self::$dotenv = Dotenv::createImmutable(dirname(__DIR__));
        self::$dotenv->safeLoad();
    }
}
