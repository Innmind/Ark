<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh\Bootstrap;

use Innmind\Ark\{
    Forge\Ovh\Bootstrap,
    Forge\Ovh\WaitTaskCompletion,
    Installation\Name,
    Exception\OvhTaskFailed,
    Exception\BootstrapFailed,
};
use Innmind\Server\Control\{
    Server,
    Server\Command,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Url\PathInterface;
use Ovh\Api;

final class Reinstall implements Bootstrap
{
    private $api;
    private $server;
    private $sshFolder;
    private $wait;

    public function __construct(
        Api $api,
        Server $server,
        PathInterface $sshFolder,
        CurrentProcess $process
    ) {
        $this->api = $api;
        $this->server = $server;
        $this->sshFolder = $sshFolder;
        $this->wait = new WaitTaskCompletion($api, $process);
    }

    public function __invoke(Name $name): void
    {
        $sshKey = $this->generateSshKey();

        $this->api->post('/me/sshKey', [
            'key' => $sshKey,
            'keyName' => (string) $name,
        ]);
        $template = $this->api->get('/vps/'.$name.'/distribution')['id'];
        try {
            $task = $this->api->post('/vps/'.$name.'/reinstall', [
                'doNotSendPassword' => true,
                'templateId' => $template,
                'sshKey' => [(string) $name],
            ]);

            ($this->wait)($name, $task['id']);
        } catch (OvhTaskFailed $e) {
            throw new BootstrapFailed((string) $name, 0, $e);
        } finally {
            $this->api->delete('/me/sshKey/'.$name);
        }
    }

    private function generateSshKey(): string
    {
        $pub = $this
            ->server
            ->processes()
            ->execute(
                Command::foreground('cat')
                    ->withArgument($this->sshFolder.'/id_rsa.pub')
            )
            ->wait();

        if ($pub->exitCode()->isSuccessful()) {
            return (string) $pub->output();
        }

        $this
            ->server
            ->processes()
            ->execute(
                Command::foreground('ssh-keygen')
                    ->withShortOption('t')
                    ->withArgument('rsa')
                    ->withShortOption('f')
                    ->withArgument($this->sshFolder.'/id_rsa')
                    ->withShortOption('N')
                    ->withArgument('')
            )
            ->wait();

        return $this->generateSshKey();
    }
}
