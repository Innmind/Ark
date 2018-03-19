<?php
declare(strict_types = 1);

namespace Innmind\Ark;

use Innmind\Ark\Installation\Name;
use Innmind\Url\UrlInterface;

final class Installation
{
    private $name;
    private $url;

    public function __construct(Name $name, UrlInterface $url)
    {
        $this->name = $name;
        $this->url = $url;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }
}
