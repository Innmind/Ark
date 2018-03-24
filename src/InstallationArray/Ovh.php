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
    private $api;
    private $available;
    private $names;

    public function __construct(Api $api, Available $available)
    {
        $this->api = $api;
        $this->available = $available;
    }

    public function current(): Installation
    {
        return new Installation(
            new Name($this->names()->current()),
            Url::fromString($this->names()->current())
        );
    }

    public function key(): Name
    {
        return $this->current()->name();
    }

    public function next(): void
    {
        $this->names()->next();
    }

    public function rewind(): void
    {
        $this->names = null;
    }

    public function valid(): bool
    {
        return $this->names()->valid();
    }

    public function count(): int
    {
        return $this->names()->size();
    }

    private function names(): Set
    {
        return $this->names ?? $this->names = Set::of(
            'string',
            ...$this->api->get('/vps')
        )->filter(function(string $name): bool {
            return !($this->available)(new Name($name));
        });
    }
}
