<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\InstallationArray;

use Innmind\Ark\{
    InstallationArray\Ovh,
    InstallationArray,
    Installation,
    Installation\Name,
    Forge\Ovh\Available,
};
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class OvhTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            InstallationArray::class,
            new Ovh(
                $this->createMock(Api::class),
                $this->createMock(Available::class)
            )
        );
    }

    public function testIterator()
    {
        $ovh = new Ovh(
            $api = $this->createMock(Api::class),
            new class implements Available {
                public function __invoke(Name $name): bool {
                    switch ($name->toString()) {
                        case 'available1':
                        case 'available2':
                        case 'available3':
                            return true;

                        default:
                            return false;
                    }
                }
            },
        );
        $api
            ->expects($this->exactly(3))
            ->method('get')
            ->with('/vps')
            ->willReturn([
                'vps42.ovh.net',
                'available1',
                'vps43.ovh.net',
                'available2',
                'vps44.ovh.net',
                'available3',
            ]);

        $this->assertNull($ovh->foreach(fn($installation) => $this->assertInstanceOf(
            Installation::class,
            $installation,
        )));

        $installations = $ovh->reduce(
            [],
            function($installations, $installation) {
                $installations[] = $installation;

                return $installations;
            },
        );

        $this->assertCount(3, $ovh);
        $this->assertCount(3, $installations);
        $this->assertSame('vps42.ovh.net', \current($installations)->name()->toString());
        $this->assertSame('vps42.ovh.net', \current($installations)->location()->toString());
        \next($installations);
        $this->assertSame('vps43.ovh.net', \current($installations)->name()->toString());
        \next($installations);
        $this->assertSame('vps44.ovh.net', \current($installations)->name()->toString());
    }
}
