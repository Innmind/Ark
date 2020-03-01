<?php
declare(strict_types = 1);

namespace Innmind\Ark;

use Innmind\Ark\Installation\Name;
use Innmind\Url\UrlInterface;

final class Installation
{
    private Name $name;
    private UrlInterface $location;

    public function __construct(Name $name, UrlInterface $location)
    {
        $this->name = $name;
        $this->location = $location;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function location(): UrlInterface
    {
        return $this->location;
    }
}
