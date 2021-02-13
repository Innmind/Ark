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
use Innmind\SshKeyProvider\Provide;
use Innmind\OperatingSystem\CurrentProcess;
use function Innmind\Immutable\first;
use Ovh\Api;

final class Reinstall implements Bootstrap
{
    private Api $api;
    private Provide $provide;
    private WaitTaskCompletion $wait;

    public function __construct(
        Api $api,
        Provide $provide,
        CurrentProcess $process
    ) {
        $this->api = $api;
        $this->provide = $provide;
        $this->wait = new WaitTaskCompletion($api, $process);
    }

    public function __invoke(Name $name): void
    {
        $sshKeys = ($this->provide)();

        if ($sshKeys->empty()) {
            throw new BootstrapFailed('A ssh key is required');
        }

        $sshKey = first($sshKeys)->toString();

        $this->api->post('/me/sshKey', [
            'key' => $sshKey,
            'keyName' => $name->toString(),
        ]);
        /** @var array{id: int, bitFormat: int, name: string, locale: string, availableLanguage: list<string>, distribution: string} */
        $distribution = $this->api->get('/vps/'.$name->toString().'/distribution');
        $template = $distribution['id'];

        try {
            /** @var array{id: int, progress: int, type: string, state: string} */
            $task = $this->api->post('/vps/'.$name->toString().'/reinstall', [
                'doNotSendPassword' => true,
                'templateId' => $template,
                'sshKey' => [$name->toString()],
            ]);

            ($this->wait)($name, $task['id']);
        } catch (OvhTaskFailed $e) {
            throw new BootstrapFailed($name->toString(), 0, $e);
        } finally {
            $this->api->delete('/me/sshKey/'.$name->toString());
        }
    }
}
