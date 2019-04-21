<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh;

use Innmind\Ark\{
    Installation\Name,
    Exception\OvhTaskFailed,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Period\Earth\Second;
use Ovh\Api;

final class WaitTaskCompletion
{
    private $api;
    private $process;

    public function __construct(Api $api, CurrentProcess $process)
    {
        $this->api = $api;
        $this->process = $process;
    }

    public function __invoke(Name $name, int $task): void
    {
        $id = $task;

        do {
            $this->process->halt(new Second(1));

            $task = $this->api->get('/vps/'.$name.'/tasks/'.$id);

            if (in_array($task['state'], ['error', 'cancelled'], true)) {
                throw new OvhTaskFailed;
            }
        } while ($task['state'] !== 'done');
    }
}
