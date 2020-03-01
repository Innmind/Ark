<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh\Dispose;

use Innmind\Ark\{
    Forge\Ovh\Dispose,
    Forge\Ovh\WaitTaskCompletion,
    Installation\Name,
    Exception\OvhTaskFailed,
    Exception\InstallationDisposalFailed,
};
use Innmind\OperatingSystem\CurrentProcess;
use Ovh\Api;

final class Stop implements Dispose
{
    private Api $api;
    private WaitTaskCompletion $wait;

    public function __construct(Api $api, CurrentProcess $process)
    {
        $this->api = $api;
        $this->wait = new WaitTaskCompletion($api, $process);
    }

    public function __invoke(Name $name): void
    {
        /** @var array{id: int, progress: int, type: string, state: string} */
        $task = $this->api->post('/vps/'.$name->toString().'/stop');

        try {
            ($this->wait)($name, $task['id']);
        } catch (OvhTaskFailed $e) {
            throw new InstallationDisposalFailed($name->toString(), 0, $e);
        }
    }
}
