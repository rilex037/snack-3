<?php

declare(strict_types=1);

namespace Snack\Routing;

use Bramus\Router\Router;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Snack\Container\ContainerInterface;
use Snack\Http\Response;

final class BramusRouter implements RouterInterface
{
    private ?Response $response = null;

    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container,
    ) {
        $this->router->set404(function (): void {
            $this->response = Response::html('404 Not Found', 404);
        });
    }

    public function get(string $pattern, callable|array $handler): void
    {
        $this->register('get', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->register('post', $pattern, $handler);
    }

    public function put(string $pattern, callable|array $handler): void
    {
        $this->register('put', $pattern, $handler);
    }

    public function patch(string $pattern, callable|array $handler): void
    {
        $this->register('patch', $pattern, $handler);
    }

    public function delete(string $pattern, callable|array $handler): void
    {
        $this->register('delete', $pattern, $handler);
    }

    public function group(string $prefix, \Closure $callback): void
    {
        $this->router->mount($prefix, function () use ($callback): void {
            $callback($this);
        });
    }

    public function dispatch(): Response
    {
        $this->router->run();

        return $this->response ?? Response::html('', 204);
    }

    private function register(string $method, string $pattern, callable|array $handler): void
    {
        $this->router->{$method}($pattern, function (string|int ...$params) use ($handler): void {
            $named = $this->mapRouteParameters($handler, $params);
            $result = $this->container->call($handler, $named);

            $this->response = $result instanceof Response ? $result : Response::html((string) $result);
        });
    }

    private function mapRouteParameters(callable|array $handler, array $routeParams): array
    {
        $reflection = is_array($handler)
            ? new ReflectionMethod($handler[0], $handler[1])
            : new ReflectionFunction(\Closure::fromCallable($handler));

        $named = [];
        $queue = $routeParams;

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $isClass = $type instanceof ReflectionNamedType && !$type->isBuiltin();

            if ($isClass || $queue === []) {
                continue;
            }

            $value = array_shift($queue);

            // Route captures always arrive as strings; coerce them to the
            // handler's declared scalar type so strict_types doesn't choke.
            $named[$parameter->getName()] = $type instanceof ReflectionNamedType
                ? match ($type->getName()) {
                    'int' => (int) $value,
                    'float' => (float) $value,
                    'bool' => (bool) $value,
                    default => (string) $value,
                }
                : $value;
        }

        return $named;
    }
}
