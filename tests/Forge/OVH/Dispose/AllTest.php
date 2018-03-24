<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\OVH\Dispose;

use Innmind\Ark\{
    Forge\OVH\Dispose\All,
    Forge\OVH\Dispose,
    Installation\Name,
};
use PHPUnit\Framework\TestCase;

class AllTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Dispose::class,
            new All
        );
    }

    public function testInvokation()
    {
        $dispose = new All(
            $mock1 = $this->createMock(Dispose::class),
            $mock2 = $this->createMock(Dispose::class),
            $mock3 = $this->createMock(Dispose::class)
        );
        $name = new Name('foo');
        $mock1
            ->expects($this->once())
            ->method('__invoke')
            ->with($name);
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($name);
        $mock3
            ->expects($this->once())
            ->method('__invoke')
            ->with($name);

        $this->assertNull($dispose($name));
    }
}
