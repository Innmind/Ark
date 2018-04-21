<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\Ovh\Dispose;

use Innmind\Ark\{
    Forge\Ovh\Dispose\Stop,
    Forge\Ovh\Dispose,
    Installation\Name,
    Exception\InstallationDisposalFailed,
};
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class StopTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Dispose::class,
            new Stop($this->createMock(Api::class))
        );
    }

    public function testInvokation()
    {
        $stop = new Stop(
            $api = $this->createMock(Api::class)
        );
        $api
            ->expects($this->once())
            ->method('post')
            ->with('/vps/foo/stop')
            ->willReturn([
                'id' => 42,
            ]);
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn(['state' => 'done']);

        $this->assertNull($stop(new Name('foo')));
    }

    public function testThrowWhenFailToStopTheServer()
    {
        $stop = new Stop(
            $api = $this->createMock(Api::class)
        );
        $api
            ->expects($this->once())
            ->method('post')
            ->with('/vps/foo/stop')
            ->willReturn([
                'id' => 42,
            ]);
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn(['state' => 'error']);

        $this->expectException(InstallationDisposalFailed::class);
        $this->expectExceptionMessage('foo');

        $stop(new Name('foo'));
    }
}
