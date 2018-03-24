<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\Ovh\Available;

use Innmind\Ark\{
    Forge\Ovh\Available,
    Installation\Name,
};
use Ovh\Api;

final class State implements Available
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function __invoke(Name $name): bool
    {
        try {
            return $this->api->get('/vps/'.$name)['state'] === 'stopped';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
