<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray\Ovh,
    InstallationArray,
    Installation,
    Installation\Name,
    Forge\Ovh\Available,
};
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class OvhTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            InstallationArray::class,
            new Ovh(
                $this->createMock(Api::class),
                $this->createMock(Available::class)
            )
        );
    }

    public function testIterator()
    {
        $ovh = new Ovh(
            $api = $this->createMock(Api::class),
            $available = $this->createMock(Available::class)
        );
        $api
            ->expects($this->exactly(2))
            ->method('get')
            ->with('/vps')
            ->willReturn([
                'vps42.ovh.net',
                'available1',
                'vps43.ovh.net',
                'available2',
                'vps44.ovh.net',
                'available3',
            ]);
        $available
            ->expects($this->at(0))
            ->method('__invoke')
            ->with(new Name('vps42.ovh.net'))
            ->willReturn(false);
        $available
            ->expects($this->at(1))
            ->method('__invoke')
            ->with(new Name('available1'))
            ->willReturn(true);
        $available
            ->expects($this->at(2))
            ->method('__invoke')
            ->with(new Name('vps43.ovh.net'))
            ->willReturn(false);
        $available
            ->expects($this->at(3))
            ->method('__invoke')
            ->with(new Name('available2'))
            ->willReturn(true);
        $available
            ->expects($this->at(4))
            ->method('__invoke')
            ->with(new Name('vps44.ovh.net'))
            ->willReturn(false);
        $available
            ->expects($this->at(5))
            ->method('__invoke')
            ->with(new Name('available3'))
            ->willReturn(true);

        $this->assertInstanceOf(Installation::class, $ovh->current());
        $this->assertInstanceOf(Name::class, $ovh->key());
        $this->assertSame('vps42.ovh.net', (string) $ovh->key());
        $this->assertSame('vps42.ovh.net', (string) $ovh->current()->name());
        $this->assertSame('vps42.ovh.net', (string) $ovh->current()->location());
        $this->assertTrue($ovh->valid());
        $this->assertNull($ovh->next());
        $this->assertSame('vps43.ovh.net', (string) $ovh->key());
        $this->assertNull($ovh->next());
        $this->assertSame('vps44.ovh.net', (string) $ovh->key());
        $this->assertNull($ovh->next());
        $this->assertFalse($ovh->valid());
        $this->assertNull($ovh->rewind());
        $this->assertSame('vps42.ovh.net', (string) $ovh->key());
    }
}
