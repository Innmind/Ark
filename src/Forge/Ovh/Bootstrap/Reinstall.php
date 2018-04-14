<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh\Bootstrap;

use Innmind\Ark\{
    Forge\Ovh\Bootstrap,
    Installation\Name,
    Exception\BootstrapFailed,
};
use Innmind\Server\Control\{
    Server,
    Server\Command,
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\Set;
use Ovh\Api;

final class Reinstall implements Bootstrap
{
    private $api;
    private $server;
    private $sshFolder;

    public function __construct(
        Api $api,
        Server $server,
        PathInterface $sshFolder
    ) {
        $this->api = $api;
        $this->server = $server;
        $this->sshFolder = $sshFolder;
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
            $this->api->post('/vps/'.$name.'/reinstall', [
                'doNotSendPassword' => true,
                'templateId' => $template,
                'sshKey' => [(string) $name],
            ]);

            $this->wait($name);
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

    private function wait(Name $name): void
    {
        do {
            sleep(1);

            $tasks = $this->api->get('/vps/'.$name.'/tasks', ['type' => 'reinstallVm']);

            $done = Set::of('mixed', ...$tasks)
                ->map(function(int $task) use ($name): array {
                    return $this->api->get('/vps/'.$name.'/tasks/'.$task);
                })
                ->foreach(static function(array $task) use ($name): void {
                    if (in_array($task['state'], ['error', 'cancelled'], true)) {
                        throw new BootstrapFailed((string) $name);
                    }
                })
                ->reduce(
                    true,
                    static function(bool $done, array $task): bool {
                        return $done && $task['state'] === 'done';
                    }
                );
        } while (!$done);
    }
}
