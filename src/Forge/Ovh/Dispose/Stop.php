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
    private $api;
    private $wait;

    public function __construct(Api $api, CurrentProcess $process)
    {
        $this->api = $api;
        $this->wait = new WaitTaskCompletion($api, $process);
    }

    public function __invoke(Name $name): void
    {
        $task = $this->api->post('/vps/'.$name.'/stop');

        try {
            ($this->wait)($name, $task['id']);
        } catch (OvhTaskFailed $e) {
            throw new InstallationDisposalFailed((string) $name, 0, $e);
        }
    }
}
