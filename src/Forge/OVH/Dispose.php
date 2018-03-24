<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\OVH;

use Innmind\Ark\Installation\Name;

interface Dispose
{
    public function __invoke(Name $name): void;
}
