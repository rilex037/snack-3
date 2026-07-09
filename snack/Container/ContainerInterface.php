<?php

declare(strict_types=1);

namespace Snack\Container;

interface ContainerInterface
{
    public function get(string $id): mixed;

    public function has(string $id): bool;

    public function bind(string $abstract, \Closure|string|null $concrete = null): void;

    public function singleton(string $abstract, \Closure|string|null $concrete = null): void;

    public function instance(string $abstract, object $instance): void;

    public function call(callable|array $callback, array $parameters = []): mixed;
}
