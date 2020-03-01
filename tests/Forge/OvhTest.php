<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge;

use Innmind\Ark\{
    Forge\Ovh,
    Forge,
    Forge\Ovh\Available,
    Forge\Ovh\Bootstrap,
    Forge\Ovh\Dispose,
    Installation,
    Installation\Name,
    Exception\RuntimeException,
    Exception\CantProvideNewInstallation,
};
use Innmind\Url\UrlInterface;
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class OVHTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Forge::class,
            new Ovh(
                $this->createMock(Api::class),
                $this->createMock(Available::class),
                $this->createMock(Bootstrap::class),
                $this->createMock(Dispose::class)
            )
        );
    }

    public function testNew()
    {
        $forge = new Ovh(
            $api = $this->createMock(Api::class),
            $available = $this->createMock(Available::class),
            $bootstrap = $this->createMock(Bootstrap::class),
            $this->createMock(Dispose::class)
        );
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps')
            ->willReturn(['foo', 'bar', 'baz']);
        $available
            ->expects($this->at(0))
            ->method('__invoke')
            ->with(new Name('foo'))
            ->willReturn(true);
        $available
            ->expects($this->at(1))
            ->method('__invoke')
            ->with(new Name('bar'))
            ->willReturn(false);
        $available
            ->expects($this->at(2))
            ->method('__invoke')
            ->with(new Name('baz'))
            ->willReturn(true);
        $bootstrap
            ->expects($this->at(0))
            ->method('__invoke')
            ->with(new Name('foo'))
            ->will($this->throwException(new RuntimeException));
        $bootstrap
            ->expects($this->at(1))
            ->method('__invoke')
            ->with(new Name('baz'));

        $installation = $forge->new();

        $this->assertInstanceOf(Installation::class, $installation);
        $this->assertSame('baz', $installation->name()->toString());
        $this->assertSame('baz', (string) $installation->location());
    }

    public function testThrowWhenNoServer()
    {
        $forge = new Ovh(
            $api = $this->createMock(Api::class),
            $available = $this->createMock(Available::class),
            $bootstrap = $this->createMock(Bootstrap::class),
            $this->createMock(Dispose::class)
        );
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps')
            ->willReturn([]);
        $available
            ->expects($this->never())
            ->method('__invoke');
        $bootstrap
            ->expects($this->never())
            ->method('__invoke');

        $this->expectException(CantProvideNewInstallation::class);

        $forge->new();
    }

    public function testThrowWhenNoServerAvailable()
    {
        $forge = new Ovh(
            $api = $this->createMock(Api::class),
            $available = $this->createMock(Available::class),
            $bootstrap = $this->createMock(Bootstrap::class),
            $this->createMock(Dispose::class)
        );
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps')
            ->willReturn(['foo']);
        $available
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(false);
        $bootstrap
            ->expects($this->never())
            ->method('__invoke');

        $this->expectException(CantProvideNewInstallation::class);

        $forge->new();
    }

    public function testDispose()
    {
        $forge = new Ovh(
            $this->createMock(Api::class),
            $this->createMock(Available::class),
            $this->createMock(Bootstrap::class),
            $dispose = $this->createMock(Dispose::class)
        );
        $installation = new Installation(
            $name = new Name('foo'),
            $this->createMock(UrlInterface::class)
        );
        $dispose
            ->expects($this->once())
            ->method('__invoke')
            ->with($name);

        $this->assertNull($forge->dispose($installation));
    }
}
