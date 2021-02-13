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
            ->expects($this->exactly(2))
            ->method('post')
            ->withConsecutive(
                [
                    '/me/sshKey',
                    [
                        'key' => 'my ssh key',
                        'keyName' => 'foo',
                    ]
                ],
                [
                    '/vps/foo/reinstall',
                    [
                        'doNotSendPassword' => true,
                        'templateId' => 143979,
                        'sshKey' => ['foo'],
                    ]
                ],
            )
            ->will($this->onConsecutiveCalls(
                null,
                [
                    'progress' => 0,
                    'id' => 42,
                    'type' => 'reinstallVm',
                    'state' => 'todo',
                ]
            ));
        $api
            ->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['/vps/foo/distribution'],
                ['/vps/foo/tasks/42'],
                ['/vps/foo/tasks/42'],
            )
            ->will($this->onConsecutiveCalls(
                [
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
                ],
                ['state' => 'doing'],
                ['state' => 'done'],
            ));
        $api
            ->expects($this->once())
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
            ->expects($this->exactly(2))
            ->method('post')
            ->withConsecutive(
                [
                    '/me/sshKey',
                    [
                        'key' => 'my ssh key',
                        'keyName' => 'foo',
                    ]
                ],
                [
                    '/vps/foo/reinstall',
                    [
                        'doNotSendPassword' => true,
                        'templateId' => 143979,
                        'sshKey' => ['foo'],
                    ]
                ],
            )
            ->will($this->onConsecutiveCalls(
                null,
                [
                    'progress' => 0,
                    'id' => 42,
                    'type' => 'reinstallVm',
                    'state' => 'todo',
                ],
            ));
        $api
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['/vps/foo/distribution'],
                ['/vps/foo/tasks/42'],
            )
            ->will($this->onConsecutiveCalls(
                [
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
                ],
                ['state' => 'error']
            ));
        $api
            ->expects($this->once())
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
            ->expects($this->exactly(2))
            ->method('post')
            ->withConsecutive(
                [
                    '/me/sshKey',
                    [
                        'key' => 'my ssh key',
                        'keyName' => 'foo',
                    ]
                ],
                [
                    '/vps/foo/reinstall',
                    [
                        'doNotSendPassword' => true,
                        'templateId' => 143979,
                        'sshKey' => ['foo'],
                    ]
                ],
            )
            ->will($this->onConsecutiveCalls(
                null,
                [
                    'progress' => 0,
                    'id' => 42,
                    'type' => 'reinstallVm',
                    'state' => 'todo',
                ],
            ));
        $api
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['/vps/foo/distribution'],
                ['/vps/foo/tasks/42'],
            )
            ->will($this->onConsecutiveCalls(
                [
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
                ],
                ['state' => 'cancelled'],
            ));
        $api
            ->expects($this->once())
            ->method('delete')
            ->with('/me/sshKey/foo');

        $this->expectException(BootstrapFailed::class);
        $this->expectExceptionMessage('foo');

        $reinstall(new Name('foo'));
    }
}
