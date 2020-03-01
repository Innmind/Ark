<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use Innmind\Ark\{
    Installation,
    Installation\Name,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class InstallationTest extends TestCase
{
    public function testInterface()
    {
        $installation = new Installation(
            $name = new Name('foo'),
            $url = Url::of('example.com')
        );

        $this->assertSame($name, $installation->name());
        $this->assertSame($url, $installation->location());
    }
}
