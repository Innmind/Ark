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
use Ovh\Api;

final class Ovh implements Forge
{
    private $api;

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
        $names = Set::of('string', ...$this->api->get('/vps'))->filter(function(string $vps): bool {
            return ($this->available)(new Name($vps));
        });

        //attempt to bootstrap a new server, if one fails it will attempt to use
        //the next available one
        while ($names->valid()) {
            try {
                ($this->bootstrap)(new Name($names->current()));

                return new Installation(
                    new Name($names->current()),
                    Url::fromString($names->current())
                );
            } catch (RuntimeException $e) {
                $names->next();
            }
        }

        throw new CantProvideNewInstallation;
    }

    public function dispose(Installation $installation): void
    {
        ($this->dispose)($installation->name());
    }
}
