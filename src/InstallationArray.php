<?php
declare(strict_types = 1);

namespace Innmind\Ark;

interface InstallationArray extends \Countable
{
    /**
     * @param callable(Installation): void $function
     */
    public function foreach(callable $function): void;

    /**
     * @template R
     *
     * @param R $initial
     * @param callable(R, Installation): R $reducer
     *
     * @return R
     */
    public function reduce($initial, callable $reducer);
    public function count(): int;
}
