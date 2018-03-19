<?php
declare(strict_types = 1);

namespace Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray,
    Installation,
    Installation\Name,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use Ovh\Api;

final class OVH implements InstallationArray
{
    private $api;
    private $names;

    public function __construct(Api $api)
    {
        $this->api = $api;
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
        //todo: filter only the installed servers
    }

    public function rewind(): void
    {
        $this->names = null;
    }

    public function valid(): bool
    {
        return $this->names()->valid();
    }

    private function names(): Set
    {
        return $this->names ?? $this->names = Set::of(
            'string',
            ...$this->api->get('/vps')
        );
    }
}
