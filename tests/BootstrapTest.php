<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use function Innmind\Ark\bootstrap;
use Innmind\Ark\Ark;
use Innmind\Url\Path;
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $ark = bootstrap(
            $this->createMock(Api::class),
            new Path('~/.ssh')
        );

        $this->assertInstanceOf(Ark::class, $ark);
    }
}
