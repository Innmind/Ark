<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\OVH\Available;

use Innmind\Ark\{
    Forge\OVH\Available\Consensus,
    Forge\OVH\Available,
    Installation\Name,
};
use PHPUnit\Framework\TestCase;

class ConsensusTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Available::class, new Consensus);
    }

    public function testConsiderAvailableByDefault()
    {
        $this->assertTrue((new Consensus)(new Name('foo')));
    }

    public function testConsensus()
    {
        $consensus = new Consensus(
            $mock1 = $this->createMock(Available::class),
            $mock2 = $this->createMock(Available::class),
            $mock3 = $this->createMock(Available::class)
        );
        $name = new Name('foo');
        $mock1
            ->expects($this->once())
            ->method('__invoke')
            ->with($name)
            ->willReturn(true);
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($name)
            ->willReturn(true);
        $mock3
            ->expects($this->once())
            ->method('__invoke')
            ->with($name)
            ->willReturn(true);

        $this->assertTrue($consensus($name));
    }

    public function testNotAvailableAsSoonAsOneReturnFalse()
    {
        $consensus = new Consensus(
            $mock1 = $this->createMock(Available::class),
            $mock2 = $this->createMock(Available::class),
            $mock3 = $this->createMock(Available::class)
        );
        $name = new Name('foo');
        $mock1
            ->expects($this->once())
            ->method('__invoke')
            ->with($name)
            ->willReturn(true);
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($name)
            ->willReturn(false);
        $mock3
            ->expects($this->never())
            ->method('__invoke');

        $this->assertFalse($consensus($name));
    }
}
