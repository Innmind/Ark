<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use Innmind\Ark\{
    Installation,
    Installation\Name,
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class InstallationTest extends TestCase
{
    public function testInterface()
    {
        $installation = new Installation(
            $name = new Name('foo'),
            $url = $this->createMock(UrlInterface::class)
        );

        $this->assertSame($name, $installation->name());
        $this->assertSame($url, $installation->location());
    }
}
