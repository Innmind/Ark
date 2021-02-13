<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh\Available;

use Innmind\Ark\{
    Forge\Ovh\Available,
    Installation\Name,
};
use Innmind\Immutable\Sequence;

final class Consensus implements Available
{
    /** @var Sequence<Available> */
    private Sequence $strategies;

    public function __construct(Available ...$strategies)
    {
        $this->strategies = Sequence::of(Available::class, ...$strategies);
    }

    public function __invoke(Name $name): bool
    {
        return $this->strategies->reduce(
            true,
            static function(bool $available, Available $strategy) use ($name): bool {
                return $available && $strategy($name);
            }
        );
    }
}
