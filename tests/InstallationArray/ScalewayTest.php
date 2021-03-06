<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray\Scaleway,
    InstallationArray,
    Installation,
};
use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Authenticated\IPs,
    Server,
    Organization,
    Image,
    IP,
    Volume,
};
use Innmind\IP\IPv4 as Address;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ScalewayTest extends TestCase
{
    public function testInterface()
    {
        $array = new Scaleway(
            $servers = $this->createMock(Servers::class),
            $ips = $this->createMock(IPs::class)
        );
        $servers
            ->expects($this->any())
            ->method('list')
            ->willReturn(Set::of(
                Server::class,
                new Server(
                    $server1 = new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7'),
                    $organization = new Organization\Id('32798ad1-7d52-4c3d-ba9d-6a93bfbd2283'),
                    new Server\Name('foo'),
                    new Image\Id('7e0d1343-c2b4-4a72-85a7-7ef6f63a28e7'),
                    $ip1 = new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40865'),
                    Server\State::running(),
                    Set::of(Server\Action::class),
                    Set::of('string'),
                    Set::of(Volume\Id::class)
                ),
                new Server(
                    new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d9'),
                    $organization,
                    new Server\Name('bar'),
                    new Image\Id('7e0d1343-c2b4-4a72-85a7-7ef6f63a28e7'),
                    new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40866'),
                    Server\State::starting(),
                    Set::of(Server\Action::class),
                    Set::of('string'),
                    Set::of(Volume\Id::class)
                ),
                new Server(
                    $server2 = new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d8'),
                    $organization,
                    new Server\Name('bar'),
                    new Image\Id('7e0d1343-c2b4-4a72-85a7-7ef6f63a28e7'),
                    $ip2 = new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40866'),
                    Server\State::running(),
                    Set::of(Server\Action::class),
                    Set::of('string'),
                    Set::of(Volume\Id::class)
                )
            ));
        $ips
            ->method('get')
            ->will($this->returnValueMap([
                [
                    $ip1,
                    new IP(
                        $ip1,
                        Address::of('127.0.0.1'),
                        $organization,
                        $server1
                    ),
                ],
                [
                    $ip2,
                    new IP(
                        $ip2,
                        Address::of('127.0.0.2'),
                        $organization,
                        $server2
                    ),
                ],
            ]));

        $this->assertNull($array->foreach(fn($installation) => $this->assertInstanceOf(
            Installation::class,
            $installation,
        )));

        $installations = $array->reduce(
            [],
            static function($installations, $installation) {
                $installations[] = $installation;

                return $installations;
            },
        );

        $this->assertSame('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7', \current($installations)->name()->toString());
        $this->assertSame('root@127.0.0.1/', \current($installations)->location()->toString());
        \next($installations);
        $this->assertSame('039aafa0-e8d6-40d5-9db5-7f2b6ed443d8', \current($installations)->name()->toString());
        $this->assertSame('root@127.0.0.2/', \current($installations)->location()->toString());
        $this->assertCount(2, $array);
    }
}
