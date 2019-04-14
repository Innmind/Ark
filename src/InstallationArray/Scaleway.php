<?php
declare(strict_types = 1);

namespace Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray,
    Installation,
    Installation\Name,
};
use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Authenticated\IPs,
    Server,
};
use Innmind\Url\{
    Url,
    NullScheme,
};
use Innmind\Immutable\SetInterface;

final class Scaleway implements InstallationArray
{
    private $servers;
    private $ips;
    private $all;

    public function __construct(Servers $servers, IPs $ips)
    {
        $this->servers = $servers;
        $this->ips = $ips;
    }

    public function current(): Installation
    {
        return new Installation(
            new Name((string) $this->all()->current()->id()),
            Url::fromString(
                'ssh://root@'.$this
                    ->ips
                    ->get($this->all()->current()->ip())
                    ->address()
            )->withScheme(new NullScheme)
        );
    }

    public function key(): Name
    {
        return $this->current()->name();
    }

    public function next(): void
    {
        $this->all()->next();
    }

    public function rewind(): void
    {
        $this->all = null;
    }

    public function valid(): bool
    {
        return $this->all()->valid();
    }

    public function count(): int
    {
        return $this->all()->size();
    }

    private function all(): SetInterface
    {
        return $this->all ?? $this->all = $this
            ->servers
            ->list()
            ->filter(static function(Server $server): bool {
                return $server->state() === Server\State::running();
            });
    }
}
