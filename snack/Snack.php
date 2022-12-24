<?php

declare(strict_types=1);

namespace Snack;

use Bramus\Router\Router;
use League\Plates\Engine;
use Snack\Orm\OrmInterface;

final class Snack
{
    private static $instance;
    private OrmInterface $orm;
    private Router $router;
    private Engine $templates;

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

    public function setRouter(Router $router): self
    {
        $this->router = $router;
        return $this;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function setTemplates(Engine $templates): self
    {
        $this->templates = $templates;
        return $this;
    }

    public function getTemplates(): Engine
    {
        return $this->templates;
    }
}
