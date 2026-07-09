<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Snack\Env;

final class EnvTest extends TestCase
{
    public function testConstructorIsPrivate(): void
    {
        $constructor = (new ReflectionClass(Env::class))->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $this->assertNull(Env::get('SNACK_TEST_UNDEFINED_KEY'));
        $this->assertSame('fallback', Env::get('SNACK_TEST_UNDEFINED_KEY', 'fallback'));
    }

    public function testHasReflectsPresence(): void
    {
        putenv('SNACK_TEST_KEY=1');
        $_ENV['SNACK_TEST_KEY'] = '1';

        $this->assertTrue(Env::has('SNACK_TEST_KEY'));
        $this->assertFalse(Env::has('SNACK_TEST_MISSING_KEY'));

        unset($_ENV['SNACK_TEST_KEY']);
        putenv('SNACK_TEST_KEY');
    }
}
