<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\OVH;

use Innmind\Ark\Exception\DomainException;

final class Template
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 1) {
            throw new DomainException((string) $value);
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}