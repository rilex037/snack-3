<?php

declare(strict_types=1);

namespace Snack;

use Dotenv\Dotenv;

final class Env
{
    private static ?Dotenv $dotenv = null;
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function get(string $key): ?string
    {
        if (self::$dotenv === null) {
            self::$dotenv = Dotenv::createImmutable(dirname(__DIR__));
            self::$dotenv->load();
        }

        return $_ENV[$key] ?? '';
    }
}
