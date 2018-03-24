<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge\OVH\Dispose;

use Innmind\Ark\{
    Forge\OVH\Dispose,
    Installation\Name,
};
use Ovh\Api;

final class Stop implements Dispose
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function __invoke(Name $name): void
    {
        $this->api->post('/vps/'.$name.'/stop');
    }
}
