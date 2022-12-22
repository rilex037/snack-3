<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;
use Snack\Orm\OrmInterface;
use Snack\Snack;

class SnackTest extends TestCase
{
    public function testGetInstanceReturnsSameInstance(): void
    {
        $snack1 = Snack::getInstance();
        $snack2 = Snack::getInstance();

        $this->assertSame($snack1, $snack2);
    }

    public function testGetOrmReturnsOrmInterface()
    {
        $orm = $this->createMock(OrmInterface::class);
        $snack = Snack::getInstance();
        $snack->setOrm($orm);
        $result = $snack->getOrm();

        $this->assertInstanceOf(OrmInterface::class, $result);
    }

    public function testCloneThrowsException()
    {
        $snack = Snack::getInstance();

        $this->expectException(\Exception::class);
        clone $snack;
    }

    public function testUnserializeThrowsException()
    {
        $snack = Snack::getInstance();

        $this->expectException(\Exception::class);
        $serializedSnack = serialize($snack);
        unserialize($serializedSnack);
    }
}
