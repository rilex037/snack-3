<?php

declare(strict_types=1);

namespace Snack\Routing;

use Snack\Http\Response;

interface RouterInterface
{
    public function get(string $pattern, callable|array $handler): void;

    public function post(string $pattern, callable|array $handler): void;

    public function put(string $pattern, callable|array $handler): void;

    public function patch(string $pattern, callable|array $handler): void;

    public function delete(string $pattern, callable|array $handler): void;

    public function group(string $prefix, \Closure $callback): void;

    public function dispatch(): Response;
}
