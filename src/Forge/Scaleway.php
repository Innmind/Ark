<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge;

use Innmind\Ark\{
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
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Period\Earth\Second;
use Innmind\Url\{
    Url,
    NullScheme,
};
use Ramsey\Uuid\Uuid;

final class Scaleway implements Forge
{
    private $servers;
    private $ips;
    private $organization;
    private $image;
    private $process;

    public function __construct(
        Servers $servers,
        IPs $ips,
        Organization\Id $organization,
        Image\Id $image,
        CurrentProcess $process
    ) {
        $this->servers = $servers;
        $this->ips = $ips;
        $this->organization = $organization;
        $this->image = $image;
        $this->process = $process;
    }

    public function new(): Installation
    {
        $ip = $this->generateIp();
        $server = $this->servers->create(
            new Server\Name((string) Uuid::uuid4()),
            $this->organization,
            $this->image,
            $ip->id()
        );
        $this->servers->execute(
            $server->id(),
            Server\Action::powerOn()
        );

        do {
            $this->process->halt(new Second(1));

            $server = $this->servers->get($server->id());
        } while ($server->state() !== Server\State::running());

        return new Installation(
            new Installation\Name((string) $server->id()),
            Url::fromString('ssh://root@'.$ip->address())->withScheme(new NullScheme)
        );
    }

    public function dispose(Installation $installation): void
    {
        $this->servers->execute(
            new Server\Id((string) $installation->name()),
            Server\Action::terminate()
        );
    }

    private function generateIp(): IP
    {
        $availableIps = $this
            ->ips
            ->list()
            ->filter(function(IP $ip): bool {
                return (string) $ip->organization() === (string) $this->organization &&
                    !$ip->attachedToAServer();
            });

        if (!$availableIps->empty()) {
            return $availableIps->current();
        }

        return $this->ips->create($this->organization);
    }
}
