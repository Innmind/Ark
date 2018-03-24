<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray\Ovh,
    InstallationArray,
    Installation,
    Installation\Name,
};
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class OvhTest extends TestCase
{
    private $api;

    public function setUp()
    {
        $this->api = $this->createMock(Api::class);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(InstallationArray::class, new Ovh($this->api));
    }

    public function testIterator()
    {
        $ovh = new Ovh($this->api);
        $this
            ->api
            ->expects($this->exactly(2))
            ->method('get')
            ->with('/vps')
            ->willReturn([
                'vps42.ovh.net',
                'vps43.ovh.net',
                'vps44.ovh.net',
            ]);

        $this->assertInstanceOf(Installation::class, $ovh->current());
        $this->assertInstanceOf(Name::class, $ovh->key());
        $this->assertSame('vps42.ovh.net', (string) $ovh->key());
        $this->assertSame('vps42.ovh.net', (string) $ovh->current()->name());
        $this->assertSame('vps42.ovh.net', (string) $ovh->current()->url());
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
