<?php
declare(strict_types = 1);

namespace Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray,
    Installation,
    Installation\Name,
    Forge\Ovh\Available,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use Ovh\Api;

final class Ovh implements InstallationArray
{
    private Api $api;
    private Available $available;

    public function __construct(Api $api, Available $available)
    {
        $this->api = $api;
        $this->available = $available;
    }

    public function foreach(callable $function): void
    {
        $this->names()->foreach(static fn(string $name) => $function(new Installation(
            new Name($name),
            Url::of($name),
        )));
    }

    public function reduce($initial, callable $reducer)
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedArgument
         */
        return $this->names()->reduce(
            $initial,
            static fn($initial, string $name) => $reducer(
                $initial,
                new Installation(
                    new Name($name),
                    Url::of($name),
                ),
            ),
        );
    }

    public function count(): int
    {
        return $this->names()->size();
    }

    /**
     * @return Set<string>
     */
    private function names(): Set
    {
        /** @var list<string> */
        $vps = $this->api->get('/vps');

        return Set::strings(...$vps)->filter(
            fn(string $name): bool => !($this->available)(new Name($name)),
        );
    }
}
