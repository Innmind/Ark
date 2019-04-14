<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use function Innmind\Ark\bootstrap;
use Innmind\Ark\Ark;
use Innmind\Url\Path;
use Innmind\OperatingSystem\OperatingSystem;
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $ark = bootstrap(
            $this->createMock(Api::class),
            new Path('~/.ssh'),
            $this->createMock(OperatingSystem::class)
        );

        $this->assertInstanceOf(Ark::class, $ark);
    }
}
