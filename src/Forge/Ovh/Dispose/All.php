<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh\Dispose;

use Innmind\Ark\{
    Forge\Ovh\Dispose,
    Installation\Name,
};

final class All implements Dispose
{
    private array $strategies;

    public function __construct(Dispose ...$strategies)
    {
        $this->strategies = $strategies;
    }

    public function __invoke(Name $name): void
    {
        foreach ($this->strategies as $dispose) {
            $dispose($name);
        }
    }
}
