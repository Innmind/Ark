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
use Innmind\Url\Url;
use Innmind\Immutable\Set;

final class Scaleway implements InstallationArray
{
    private Servers $servers;
    private IPs $ips;

    public function __construct(Servers $servers, IPs $ips)
    {
        $this->servers = $servers;
        $this->ips = $ips;
    }

    public function foreach(callable $function): void
    {
        $this->all()->foreach(
            fn(Server $server) => $function($this->bridge($server)),
        );
    }

    public function reduce($initial, callable $reducer)
    {
        /**
         * @psalm-suppress MissingParamType
         * @psalm-suppress MixedArgument
         */
        return $this->all()->reduce(
            $initial,
            fn($initial, Server $server) => $reducer(
                $initial,
                $this->bridge($server),
            ),
        );
    }

    public function count(): int
    {
        return $this->all()->size();
    }

    private function bridge(Server $server): Installation
    {
        return new Installation(
            new Name($server->id()->toString()),
            Url::of(
                'ssh://root@'.$this
                    ->ips
                    ->get($server->ip())
                    ->address()
                    ->toString(),
            )->withoutScheme(),
        );
    }

    private function all(): Set
    {
        return $this
            ->servers
            ->list()
            ->filter(static function(Server $server): bool {
                return $server->state() === Server\State::running();
            });
    }
}
