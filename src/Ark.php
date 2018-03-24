<?php
declare(strict_types = 1);

namespace Innmind\Ark;

final class Ark
{
    private $forge;
    private $array;

    public function __construct(Forge $forge, InstallationArray $array)
    {
        $this->forge = $forge;
        $this->array = $array;
    }

    public function forge(): Forge
    {
        return $this->forge;
    }

    public function array(): InstallationArray
    {
        return $this->array;
    }
}
