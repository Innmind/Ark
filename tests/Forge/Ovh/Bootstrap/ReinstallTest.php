<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\Ovh\Bootstrap;

use Innmind\Ark\{
    Forge\Ovh\Bootstrap\Reinstall,
    Forge\Ovh\Bootstrap,
    Installation\Name,
    Exception\BootstrapFailed,
};
use Innmind\SshKeyProvider\{
    Provide,
    PublicKey,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Immutable\Set;
use Ovh\Api;
use PHPUnit\Framework\TestCase;

class ReinstallTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Bootstrap::class,
            new Reinstall(
                $this->createMock(Api::class),
                $this->createMock(Provide::class),
                $this->createMock(CurrentProcess::class)
            )
        );
    }

    public function testInvokation()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $provider = $this->createMock(Provide::class),
            $this->createMock(CurrentProcess::class)
        );
        $provider
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Set::of(
                PublicKey::class,
                new PublicKey('my ssh key')
            ));
        $api
            ->expects($this->at(0))
            ->method('post')
            ->with(
                '/me/sshKey',
                [
                    'key' => 'my ssh key',
                    'keyName' => 'foo',
                ]
            );
        $api
            ->expects($this->at(1))
            ->method('get')
            ->with('/vps/foo/distribution')
            ->willReturn([
                'bitFormat' => 64,
                'name' => 'Debian 9 (Stretch)',
                'id' => 143979,
                'locale' => 'en',
                'availableLanguage' => [
                    'en',
                    'fr',
                    'es',
                    'de',
                    'pl',
                    'pt',
                    'it',
                    'nl'
                ],
                'distribution' => 'debian9'
            ]);
        $api
            ->expects($this->at(2))
            ->method('post')
            ->with(
                '/vps/foo/reinstall',
                [
                    'doNotSendPassword' => true,
                    'templateId' => 143979,
                    'sshKey' => ['foo'],
                ]
            )
            ->willReturn([
                'progress' => 0,
                'id' => 42,
                'type' => 'reinstallVm',
                'state' => 'todo',
            ]);
        $api
            ->expects($this->at(3))
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn(['state' => 'doing']);
        $api
            ->expects($this->at(4))
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn(['state' => 'done']);
        $api
            ->expects($this->at(5))
            ->method('delete')
            ->with('/me/sshKey/foo');

        $this->assertNull($reinstall(new Name('foo')));
    }

    public function testThrowWhenNoSshKey()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $provider = $this->createMock(Provide::class),
            $this->createMock(CurrentProcess::class)
        );
        $provider
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Set::of(PublicKey::class));

        $this->expectException(BootstrapFailed::class);

        $reinstall(new Name('foo'));
    }

    public function testThrowWhenTaskFailed()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $provider = $this->createMock(Provide::class),
            $this->createMock(CurrentProcess::class)
        );
        $provider
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Set::of(
                PublicKey::class,
                new PublicKey('my ssh key')
            ));
        $api
            ->expects($this->at(0))
            ->method('post')
            ->with(
                '/me/sshKey',
                [
                    'key' => 'my ssh key',
                    'keyName' => 'foo',
                ]
            );
        $api
            ->expects($this->at(1))
            ->method('get')
            ->with('/vps/foo/distribution')
            ->willReturn([
                'bitFormat' => 64,
                'name' => 'Debian 9 (Stretch)',
                'id' => 143979,
                'locale' => 'en',
                'availableLanguage' => [
                    'en',
                    'fr',
                    'es',
                    'de',
                    'pl',
                    'pt',
                    'it',
                    'nl'
                ],
                'distribution' => 'debian9'
            ]);
        $api
            ->expects($this->at(2))
            ->method('post')
            ->with(
                '/vps/foo/reinstall',
                [
                    'doNotSendPassword' => true,
                    'templateId' => 143979,
                    'sshKey' => ['foo'],
                ]
            )
            ->willReturn([
                'progress' => 0,
                'id' => 42,
                'type' => 'reinstallVm',
                'state' => 'todo',
            ]);
        $api
            ->expects($this->at(3))
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn(['state' => 'error']);
        $api
            ->expects($this->at(4))
            ->method('delete')
            ->with('/me/sshKey/foo');

        $this->expectException(BootstrapFailed::class);
        $this->expectExceptionMessage('foo');

        $reinstall(new Name('foo'));
    }

    public function testThrowWhenTaskCancelled()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $provider = $this->createMock(Provide::class),
            $this->createMock(CurrentProcess::class)
        );
        $provider
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Set::of(
                PublicKey::class,
                new PublicKey('my ssh key')
            ));
        $api
            ->expects($this->at(0))
            ->method('post')
            ->with(
                '/me/sshKey',
                [
                    'key' => 'my ssh key',
                    'keyName' => 'foo',
                ]
            );
        $api
            ->expects($this->at(1))
            ->method('get')
            ->with('/vps/foo/distribution')
            ->willReturn([
                'bitFormat' => 64,
                'name' => 'Debian 9 (Stretch)',
                'id' => 143979,
                'locale' => 'en',
                'availableLanguage' => [
                    'en',
                    'fr',
                    'es',
                    'de',
                    'pl',
                    'pt',
                    'it',
                    'nl'
                ],
                'distribution' => 'debian9'
            ]);
        $api
            ->expects($this->at(2))
            ->method('post')
            ->with(
                '/vps/foo/reinstall',
                [
                    'doNotSendPassword' => true,
                    'templateId' => 143979,
                    'sshKey' => ['foo'],
                ]
            )
            ->willReturn([
                'progress' => 0,
                'id' => 42,
                'type' => 'reinstallVm',
                'state' => 'todo',
            ]);
        $api
            ->expects($this->at(3))
            ->method('get')
            ->with('/vps/foo/tasks/42')
            ->willReturn(['state' => 'cancelled']);
        $api
            ->expects($this->at(4))
            ->method('delete')
            ->with('/me/sshKey/foo');

        $this->expectException(BootstrapFailed::class);
        $this->expectExceptionMessage('foo');

        $reinstall(new Name('foo'));
    }
}
