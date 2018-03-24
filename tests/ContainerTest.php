<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark;

use Innmind\Ark\{
    Ark,
    Forge\Ovh\Template,
};
use Innmind\Url\Path;
use Innmind\Compose\ContainerBuilder\ContainerBuilder;
use Innmind\Immutable\Map;
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainer()
    {
        $container = (new ContainerBuilder)(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('api', $this->createMock(Api::class))
                ->put('sshFolder', new Path('~/.ssh'))
                ->put('template', new Template(143979))
        );

        $this->assertInstanceOf(Ark::class, $container->get('ark'));
    }
}
