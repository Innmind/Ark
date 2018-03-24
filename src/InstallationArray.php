<?php
declare(strict_types = 1);

namespace Innmind\Ark;

use Innmind\Ark\Installation\Name;

interface InstallationArray extends \Iterator, \Countable
{
    public function current(): Installation;
    public function key(): Name;
    public function next(): void;
    public function rewind(): void;
    public function valid(): bool;
    public function count(): int;
}
