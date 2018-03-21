<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\OVH\Available;

use Innmind\Ark\{
    Forge\OVH\Available\State,
    Forge\OVH\Available,
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
            ->expects($this->at(0))
            ->method('get')
            ->with('/vps/foo')
            ->willReturn(['state' => 'stopped']);
        $api
            ->expects($this->at(1))
            ->method('get')
            ->with('/vps/foo')
            ->willReturn(['state' => 'running']);
        $api
            ->expects($this->at(2))
            ->method('get')
            ->with('/vps/foo')
            ->will($this->throwException(new \Exception));

        $this->assertTrue($state(new Name('foo')));
        $this->assertFalse($state(new Name('foo')));
        $this->assertFalse($state(new Name('foo')));
    }
}
