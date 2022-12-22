<?php

declare(strict_types=1);

namespace Snack;

use Snack\Orm\OrmInterface;

class Snack
{
    private static $instance;
    private $orm;

    private function __construct()
    {
    }

    public static function getInstance(): Snack
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __clone()
    {
        throw new \Exception("Cannot cnone a singleton.");
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public function setOrm(OrmInterface $orm): void
    {
        $this->orm = $orm;
    }

    public function getOrm(): OrmInterface
    {
        return $this->orm;
    }
}
