<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use function Innmind\Ark\{
    ovh,
    scaleway,
};
use Innmind\Ark\Ark;
use Innmind\Url\Path;
use Innmind\OperatingSystem\{
    OperatingSystem,
    CurrentProcess,
};
use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Authenticated\IPs,
    Organization,
    Image,
};
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testOvh()
    {
        $ark = ovh(
            $this->createMock(Api::class),
            new Path('~/.ssh'),
            $this->createMock(OperatingSystem::class)
        );

        $this->assertInstanceOf(Ark::class, $ark);
    }

    public function testScaleway()
    {
        $ark = scaleway(
            $this->createMock(Servers::class),
            $this->createMock(IPs::class),
            $this->createMock(CurrentProcess::class),
            new Organization\Id('e2d781a9-b8cf-401b-8896-fb5749f331c5'),
            new Image\Id('f7b3048d-b1d6-4989-8adb-5635d34a6c7a')
        );

        $this->assertInstanceOf(Ark::class, $ark);
    }
}
