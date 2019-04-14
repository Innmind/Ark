<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge;

use Innmind\Ark\{
    Forge\Scaleway,
    Forge,
    Installation,
};
use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Authenticated\IPs,
    Organization,
    Image,
    Server,
    IP,
    Volume,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\IP\IPv4 as Address;
use Innmind\TimeContinuum\Period\Earth\Second;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ScalewayTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Forge::class,
            new Scaleway(
                $this->createMock(Servers::class),
                $this->createMock(IPs::class),
                new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
                new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6'),
                $this->createMock(CurrentProcess::class)
            )
        );
    }

    public function testNew()
    {
        $forge = new Scaleway(
            $servers = $this->createMock(Servers::class),
            $ips = $this->createMock(IPs::class),
            $organization = new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
            $image = new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6'),
            $process = $this->createMock(CurrentProcess::class)
        );
        $ips
            ->expects($this->once())
            ->method('create')
            ->with($organization)
            ->willReturn($ip = new IP(
                new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40865'),
                new Address('127.0.0.1'),
                $organization,
                null
            ));
        $servers
            ->expects($this->at(0))
            ->method('create')
            ->with(
                $this->isInstanceOf(Server\Name::class),
                $organization,
                $image
            )
            ->willReturn($server = new Server(
                new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7'),
                $organization,
                new Server\Name('foo'),
                $image,
                $ip->id(),
                Server\State::starting(),
                Set::of(Server\Action::class),
                Set::of('string'),
                Set::of(Volume\Id::class)
            ));
        $servers
            ->expects($this->at(1))
            ->method('execute')
            ->with($server->id(), Server\Action::powerOn());
        $servers
            ->expects($this->at(2))
            ->method('get')
            ->with($server->id())
            ->willReturn($server);
        $servers
            ->expects($this->at(3))
            ->method('get')
            ->with($server->id())
            ->willReturn($server = new Server(
                new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7'),
                $organization,
                new Server\Name('foo'),
                $image,
                $ip = new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40865'),
                Server\State::running(),
                Set::of(Server\Action::class),
                Set::of('string'),
                Set::of(Volume\Id::class)
            ));
        $process
            ->expects($this->exactly(2))
            ->method('halt')
            ->with(new Second(1));

        $installation = $forge->new();

        $this->assertInstanceOf(Installation::class, $installation);
        $this->assertSame('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7', (string) $installation->name());
        $this->assertSame('root@127.0.0.1/', (string) $installation->location());
    }

    public function testDispose()
    {
        $forge = new Scaleway(
            $servers = $this->createMock(Servers::class),
            $this->createMock(IPs::class),
            new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
            new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6'),
            $this->createMock(CurrentProcess::class)
        );
        $servers
            ->expects($this->once())
            ->method('execute')
            ->with(
                new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7'),
                Server\Action::terminate()
            );

        $this->assertNull($forge->dispose(new Installation(
            new Installation\Name('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7'),
            $this->createMock(UrlInterface::class)
        )));
    }
}
