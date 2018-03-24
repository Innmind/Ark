<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\Ovh;

use Innmind\Ark\{
    Forge\Ovh\Template,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    TestTrait,
    Generator,
};

class TemplateTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this
            ->forAll(Generator\pos())
            ->then(function(int $int): void {
                $this->assertSame($int, (new Template($int))->toInt());
            });
    }

    public function testThrowWhenNegative()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage((string) $int);

                new Template($int);
            });
    }
}
