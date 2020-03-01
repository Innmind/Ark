<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge;

use Innmind\Ark\{
    Forge,
    Forge\Ovh\Available,
    Forge\Ovh\Bootstrap,
    Forge\Ovh\Dispose,
    Installation,
    Installation\Name,
    Exception\RuntimeException,
    Exception\CantProvideNewInstallation,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use Ovh\Api;

final class Ovh implements Forge
{
    private Api $api;
    private Available $available;
    private Bootstrap $bootstrap;
    private Dispose $dispose;

    public function __construct(
        Api $api,
        Available $available,
        Bootstrap $bootstrap,
        Dispose $dispose
    ) {
        $this->api = $api;
        $this->available = $available;
        $this->bootstrap = $bootstrap;
        $this->dispose = $dispose;
    }

    public function new(): Installation
    {
        //iterate over existing vps and filter the ones "available" as the api
        //doesn't allow to order a new vps on demand, so instead we look for an
        //existing purchased vps that's available
        $names = Set::strings(...$this->api->get('/vps'))->filter(function(string $vps): bool {
            return ($this->available)(new Name($vps));
        });
        $names = unwrap($names);

        //attempt to bootstrap a new server, if one fails it will attempt to use
        //the next available one
        while (\is_string(\current($names))) {
            try {
                ($this->bootstrap)(new Name(\current($names)));

                return new Installation(
                    new Name(\current($names)),
                    Url::of(\current($names)),
                );
            } catch (RuntimeException $e) {
                \next($names);
            }
        }

        throw new CantProvideNewInstallation;
    }

    public function dispose(Installation $installation): void
    {
        ($this->dispose)($installation->name());
    }
}
