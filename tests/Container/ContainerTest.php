<?php

declare(strict_types=1);

namespace Test\Container;

use PHPUnit\Framework\TestCase;
use Snack\Container\Container;
use Snack\Exception\ContainerException;
use Snack\Exception\NotFoundException;

interface Greeter
{
    public function greet(): string;
}

final class EnglishGreeter implements Greeter
{
    public function greet(): string
    {
        return 'hello';
    }
}

final class Signature
{
    public function __construct(private readonly Greeter $greeter, private readonly string $suffix = '!')
    {
    }

    public function say(): string
    {
        return $this->greeter->greet() . $this->suffix;
    }
}

abstract class Abstracty
{
}

final class ContainerTest extends TestCase
{
    public function testAutowiresConcreteClassesWithNoConstructor(): void
    {
        $container = new Container();

        $this->assertInstanceOf(EnglishGreeter::class, $container->get(EnglishGreeter::class));
    }

    public function testBindResolvesInterfaceToConcrete(): void
    {
        $container = new Container();
        $container->bind(Greeter::class, EnglishGreeter::class);

        $this->assertInstanceOf(EnglishGreeter::class, $container->get(Greeter::class));
    }

    public function testSingletonReturnsTheSameInstanceEveryTime(): void
    {
        $container = new Container();
        $container->singleton(EnglishGreeter::class);

        $this->assertSame($container->get(EnglishGreeter::class), $container->get(EnglishGreeter::class));
    }

    public function testBindReturnsANewInstanceEveryTime(): void
    {
        $container = new Container();
        $container->bind(EnglishGreeter::class);

        $this->assertNotSame($container->get(EnglishGreeter::class), $container->get(EnglishGreeter::class));
    }

    public function testInstanceRegistersAnAlreadyBuiltObject(): void
    {
        $container = new Container();
        $greeter = new EnglishGreeter();
        $container->instance(Greeter::class, $greeter);

        $this->assertSame($greeter, $container->get(Greeter::class));
    }

    public function testAutowiresNestedConstructorDependencies(): void
    {
        $container = new Container();
        $container->bind(Greeter::class, EnglishGreeter::class);

        $signature = $container->get(Signature::class);

        $this->assertSame('hello!', $signature->say());
    }

    public function testThrowsWhenClassIsUnknown(): void
    {
        $this->expectException(NotFoundException::class);

        (new Container())->get('Totally\\Unknown\\Class');
    }

    public function testThrowsWhenClassIsNotInstantiable(): void
    {
        $this->expectException(ContainerException::class);

        (new Container())->get(Abstracty::class);
    }

    public function testCallAutowiresMissingArgumentsOnAClosure(): void
    {
        $container = new Container();
        $container->bind(Greeter::class, EnglishGreeter::class);

        $result = $container->call(function (Greeter $greeter, string $suffix = '?'): string {
            return $greeter->greet() . $suffix;
        });

        $this->assertSame('hello?', $result);
    }

    public function testCallAutowiresMissingArgumentsOnAMethod(): void
    {
        $container = new Container();
        $container->bind(Greeter::class, EnglishGreeter::class);

        $result = $container->call([Signature::class, 'say']);

        $this->assertSame('hello!', $result);
    }

    public function testCallPrefersExplicitlyGivenParameters(): void
    {
        $container = new Container();
        $container->bind(Greeter::class, EnglishGreeter::class);

        $result = $container->call(
            fn (Greeter $greeter, string $suffix) => $greeter->greet() . $suffix,
            ['suffix' => '???']
        );

        $this->assertSame('hello???', $result);
    }
}
