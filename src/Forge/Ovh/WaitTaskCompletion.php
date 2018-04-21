<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh;

use Innmind\Ark\{
    Installation\Name,
    Exception\OvhTaskFailed,
};
use Ovh\Api;

final class WaitTaskCompletion
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function __invoke(Name $name, int $task): void
    {
        $id = $task;

        do {
            sleep(1);

            $task = $this->api->get('/vps/'.$name.'/tasks/'.$id);

            if (in_array($task['state'], ['error', 'cancelled'], true)) {
                throw new OvhTaskFailed;
            }
        } while ($task['state'] !== 'done');
    }
}
