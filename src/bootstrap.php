<?php
declare(strict_types = 1);

namespace Innmind\Ark;

use Innmind\Url\PathInterface;
use Innmind\OperatingSystem\OperatingSystem;
use Ovh\Api;

function bootstrap(
    Api $api,
    PathInterface $sshFolder,
    OperatingSystem $os
): Ark {
    $server = $os->control();

    return new Ark(
        new Forge\Ovh(
            $api,
            $available = new Forge\Ovh\Available\State($api),
            new Forge\Ovh\Bootstrap\Reinstall($api, $server, $sshFolder),
            new Forge\Ovh\Dispose\Stop($api)
        ),
        new InstallationArray\Ovh($api, $available)
    );
}
