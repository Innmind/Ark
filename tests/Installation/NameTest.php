<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Installation;

use Innmind\Ark\{
    Installation\Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testAcceptAnyNonEmptyString()
    {
        $this
            ->forAll(Set\Strings::atLeast(1))
            ->then(function(string $string): void {
                $this->assertSame($string, (new Name($string))->toString());
            });
    }

    public function testThrowWhenEmptyString()
    {
        $this->expectException(DomainException::class);

        new Name('');
    }
}
