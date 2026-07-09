<?php

declare(strict_types=1);

namespace Snack\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Snack\Exception\ContainerException;
use Snack\Exception\NotFoundException;

class Container implements ContainerInterface
{
    private array $bindings = [];

    private array $shared = [];

    private array $instances = [];

    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->register($abstract, $concrete, shared: false);
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->register($abstract, $concrete, shared: true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $resolved = isset($this->bindings[$id])
            ? $this->bindings[$id]($this)
            : $this->autowire($id);

        if (isset($this->shared[$id])) {
            $this->instances[$id] = $resolved;
        }

        return $resolved;
    }

    public function call(callable|array $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            [$target, $method] = $callback;
            $instance = is_string($target) ? $this->get($target) : $target;
            $reflection = new ReflectionMethod($instance, $method);
            $args = $this->resolveParameters($reflection->getParameters(), $parameters);

            return $reflection->invokeArgs($instance, $args);
        }

        $reflection = new ReflectionFunction(Closure::fromCallable($callback));
        $args = $this->resolveParameters($reflection->getParameters(), $parameters);

        return $callback(...$args);
    }

    private function register(string $abstract, Closure|string|null $concrete, bool $shared): void
    {
        unset($this->instances[$abstract]);

        $concrete ??= $abstract;

        $this->bindings[$abstract] = $concrete instanceof Closure
            ? $concrete
            : fn (self $container): mixed => $container->autowire($concrete);

        if ($shared) {
            $this->shared[$abstract] = true;
        }
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new NotFoundException("Cannot resolve unknown class or binding [{$class}].");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Target [{$class}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $args = $this->resolveParameters($constructor->getParameters(), []);

        return $reflection->newInstanceArgs($args);
    }

    private function resolveParameters(array $parameters, array $given): array
    {
        $args = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $given)) {
                $args[] = $given[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();
                $args[] = $this->get($className);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new ContainerException(
                "Cannot resolve parameter [{$name}]: no binding, default value, or type hint available."
            );
        }

        return $args;
    }
}
