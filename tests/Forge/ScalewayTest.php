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
    Authenticated\Users,
    Organization,
    Image,
    Server,
    IP,
    Volume,
    User,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\IP\IPv4 as Address;
use Innmind\TimeContinuum\Period\Earth\Second;
use Innmind\Url\UrlInterface;
use Innmind\SshKeyProvider\{
    Provide,
    PublicKey,
};
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
                $this->createMock(Users::class),
                $this->createMock(CurrentProcess::class),
                $this->createMock(Provide::class),
                new User\Id('f8b3a7be-e750-4dc1-9c07-7e42844e21d9'),
                new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
                new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6')
            )
        );
    }

    public function testNew()
    {
        $forge = new Scaleway(
            $servers = $this->createMock(Servers::class),
            $ips = $this->createMock(IPs::class),
            $users = $this->createMock(Users::class),
            $process = $this->createMock(CurrentProcess::class),
            $provider = $this->createMock(Provide::class),
            $user = new User\Id('f8b3a7be-e750-4dc1-9c07-7e42844e21d9'),
            $organization = new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
            $image = new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6')
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
        $ips
            ->expects($this->once())
            ->method('list')
            ->willReturn(Set::of(IP::class));
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
        $users
            ->expects($this->once())
            ->method('get')
            ->with($user)
            ->willReturn(new User(
                $user,
                'foo',
                'bar',
                'baz',
                'foobar',
                Set::of(
                    User\SshKey::class,
                    $foo = new User\SshKey('foo'),
                    $baz = new User\SshKey('baz')
                ),
                Set::of(Organization\Id::class, $organization)
            ));
        $provider
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Set::of(
                PublicKey::class,
                new PublicKey('foo'),
                new PublicKey('bar')
            ));
        $users
            ->expects($this->once())
            ->method('updateSshKeys')
            ->with(
                $user,
                $foo,
                $baz,
                new User\SshKey('bar')
            );

        $installation = $forge->new();

        $this->assertInstanceOf(Installation::class, $installation);
        $this->assertSame('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7', (string) $installation->name());
        $this->assertSame('root@127.0.0.1/', (string) $installation->location());
    }

    public function testReuseUnusedIPWhenCreatingServer()
    {
        $forge = new Scaleway(
            $servers = $this->createMock(Servers::class),
            $ips = $this->createMock(IPs::class),
            $users = $this->createMock(Users::class),
            $process = $this->createMock(CurrentProcess::class),
            $provider = $this->createMock(Provide::class),
            $user = new User\Id('f8b3a7be-e750-4dc1-9c07-7e42844e21d9'),
            $organization = new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
            $image = new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6')
        );
        $ips
            ->expects($this->never())
            ->method('create');
        $ips
            ->expects($this->once())
            ->method('list')
            ->willReturn(Set::of(
                IP::class,
                new IP(
                    new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40865'),
                    new Address('127.0.0.3'),
                    $organization,
                    new Server\Id('8d923ccf-8148-4233-9b3e-589c34549d96')
                ),
                new IP(
                    new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40864'),
                    new Address('127.0.0.2'),
                    new Organization\Id('e51a0dc0-9386-42f0-930c-afb95917b847'),
                    null
                ),
                $ip = new IP(
                    new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40865'),
                    new Address('127.0.0.1'),
                    $organization,
                    null
                )
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
        $users
            ->expects($this->once())
            ->method('get')
            ->with($user)
            ->willReturn(new User(
                $user,
                'foo',
                'bar',
                'baz',
                'foobar',
                Set::of(
                    User\SshKey::class,
                    $foo = new User\SshKey('foo'),
                    $baz = new User\SshKey('baz')
                ),
                Set::of(Organization\Id::class, $organization)
            ));
        $provider
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Set::of(
                PublicKey::class,
                new PublicKey('foo'),
                new PublicKey('bar')
            ));
        $users
            ->expects($this->once())
            ->method('updateSshKeys')
            ->with(
                $user,
                $foo,
                $baz,
                new User\SshKey('bar')
            );

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
            $this->createMock(Users::class),
            $this->createMock(CurrentProcess::class),
            $this->createMock(Provide::class),
            new User\Id('f8b3a7be-e750-4dc1-9c07-7e42844e21d9'),
            new Organization\Id('e34fce59-e05d-4adc-90fb-1085d6beb837'),
            new Image\Id('ceae2662-88db-406a-88a7-34bb888bbdd6')
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
