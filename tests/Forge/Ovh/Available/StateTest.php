<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\Ovh\Available;

use Innmind\Ark\{
    Forge\Ovh\Available\State,
    Forge\Ovh\Available,
    Installation\Name,
};
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Available::class,
            new State($this->createMock(APi::class))
        );
    }

    public function testInvokation()
    {
        $state = new State(
            $api = $this->createMock(Api::class)
        );
        $api
            ->expects($this->exactly(3))
            ->method('get')
            ->with('/vps/foo')
            ->will($this->onConsecutiveCalls(
                ['state' => 'stopped'],
                ['state' => 'running'],
                $this->throwException(new \Exception)
            ));

        $this->assertTrue($state(new Name('foo')));
        $this->assertFalse($state(new Name('foo')));
        $this->assertFalse($state(new Name('foo')));
    }
}
