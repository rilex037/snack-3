<?php

declare(strict_types=1);

namespace Snack;

use Snack\Orm\OrmInterface;

final class Snack
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
        throw new \Exception();
    }

    public function __wakeup()
    {
        throw new \Exception();
    }

    public function setOrm(OrmInterface $orm): self
    {
        $this->orm = $orm;
        return $this;
    }

    public function getOrm(): OrmInterface
    {
        return $this->orm;
    }

    public function setRouter($router): self
    {
        $this->router = $router;
        return $this;
    }
}
