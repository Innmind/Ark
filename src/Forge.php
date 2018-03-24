<?php
declare(strict_types = 1);

namespace Innmind\Ark;

interface Forge
{
    public function new(): Installation;
    public function dispose(Installation $installation): void;
}
