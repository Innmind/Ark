<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use Innmind\Ark\{
    Ark,
    Forge,
    InstallationArray,
};
use PHPUnit\Framework\TestCase;

class ArkTest extends TestCase
{
    public function testInterface()
    {
        $ark = new Ark(
            $forge = $this->createMock(Forge::class),
            $array = $this->createMock(InstallationArray::class)
        );

        $this->assertSame($forge, $ark->forge());
        $this->assertSame($array, $ark->array());
    }
}
