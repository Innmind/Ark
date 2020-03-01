<?php
declare(strict_types = 1);

namespace Innmind\Ark;

use Innmind\Ark\Installation\Name;
use Innmind\Url\Url;

final class Installation
{
    private Name $name;
    private Url $location;

    public function __construct(Name $name, Url $location)
    {
        $this->name = $name;
        $this->location = $location;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function location(): Url
    {
        return $this->location;
    }
}
