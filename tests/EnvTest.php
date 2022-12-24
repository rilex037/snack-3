<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Snack\Env;

final class EnvTest extends TestCase
{
    public function testConstructorThrowsError(): void
    {
        $reflection = new ReflectionClass('Snack\Env');
        $constructor = $reflection->getConstructor();
        $this->assertTrue($constructor->isPrivate());
        var_dump($constructor->isPrivate());
    }

    public function testGet(): void
    {
        $this->assertEquals('localhost', Env::get('DB_HOST'));
        $this->assertEquals(Env::get('INVALID_KEY'), '');
    }
}
