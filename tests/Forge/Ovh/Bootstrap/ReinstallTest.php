<?php
declare(strict_types = 1);

namespace Tests\Innmind\Ark\Forge\Ovh\Bootstrap;

use Innmind\Ark\{
    Forge\Ovh\Bootstrap\Reinstall,
    Forge\Ovh\Bootstrap,
    Installation\Name,
    Exception\BootstrapFailed,
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
    Server\Process\Output,
};
use Innmind\Url\Path;
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
                $this->createMock(Server::class),
                new Path('.ssh')
            )
        );
    }

    public function testInvokation()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $server = $this->createMock(Server::class),
            new Path('/home/user/.ssh')
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "cat '/home/user/.ssh/id_rsa.pub'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('my ssh key');
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

    public function testGenerateSshKeyWhenNonePresent()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $server = $this->createMock(Server::class),
            new Path('/home/user/.ssh')
        );
        $server
            ->expects($this->exactly(3))
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "cat '/home/user/.ssh/id_rsa.pub'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));
        $process
            ->expects($this->never())
            ->method('output');
        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "ssh-keygen '-t' 'rsa' '-f' '/home/user/.ssh/id_rsa' '-N' ''";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $processes
            ->expects($this->at(2))
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "cat '/home/user/.ssh/id_rsa.pub'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('my ssh key');
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

    public function testThrowWhenTaskFailed()
    {
        $reinstall = new Reinstall(
            $api = $this->createMock(Api::class),
            $server = $this->createMock(Server::class),
            new Path('/home/user/.ssh')
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "cat '/home/user/.ssh/id_rsa.pub'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('my ssh key');
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
            $server = $this->createMock(Server::class),
            new Path('/home/user/.ssh')
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "cat '/home/user/.ssh/id_rsa.pub'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('my ssh key');
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
