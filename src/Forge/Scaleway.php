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
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Period\Earth\Second;
use Innmind\Url\Url;
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
        $server = $this->servers->create(
            new Server\Name((string) Uuid::uuid4()),
            $this->organization,
            $this->image
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
            Url::fromString(
                (string) $this
                    ->ips
                    ->get($server->ip())
                    ->address()
            )
        );
    }

    public function dispose(Installation $installation): void
    {
        $this->servers->execute(
            new Server\Id((string) $installation->name()),
            Server\Action::terminate()
        );
    }
}
