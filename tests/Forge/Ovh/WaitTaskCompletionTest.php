<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\Ovh;

use Innmind\Ark\{
    Forge\Ovh\WaitTaskCompletion,
    Installation\Name,
    Exception\OvhTaskFailed,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Earth\Period\Second;
use Ovh\Api;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class WaitTaskCompletionTest extends TestCase
{
    use BlackBox;

    public function testInvokation()
    {
        $this
            ->forAll(Set\Strings::atLeast(1), Set\Integers::above(0))
            ->then(function(string $name, int $task): void {
                $wait = new WaitTaskCompletion(
                    $api = $this->createMock(Api::class),
                    $process = $this->createMock(CurrentProcess::class)
                );
                $api
                    ->expects($this->exactly(2))
                    ->method('get')
                    ->with("/vps/$name/tasks/$task")
                    ->will($this->onConsecutiveCalls(
                        ['state' => 'doing'],
                        ['state' => 'done'],
                    ));
                $process
                    ->expects($this->exactly(2))
                    ->method('halt')
                    ->with(new Second(1));

                $this->assertNull($wait(new Name($name), $task));
            });
    }

    public function testThrowWhenTaskErrored()
    {
        $wait = new WaitTaskCompletion(
            $api = $this->createMock(Api::class),
            $this->createMock(CurrentProcess::class)
        );
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn([
                'state' => 'error',
            ]);

        $this->expectException(OvhTaskFailed::class);

        $wait(new Name('foo'), 42);
    }

    public function testThrowWhenTaskCanceled()
    {
        $wait = new WaitTaskCompletion(
            $api = $this->createMock(Api::class),
            $this->createMock(CurrentProcess::class)
        );
        $api
            ->expects($this->once())
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn([
                'state' => 'cancelled',
            ]);

        $this->expectException(OvhTaskFailed::class);

        $wait(new Name('foo'), 42);
    }
}
