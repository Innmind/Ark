<?php
declare(strict_types = 1);

namespace Innmind\Ark;

use Innmind\Url\PathInterface;
use Innmind\OperatingSystem\{
    OperatingSystem,
    CurrentProcess,
};
use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Authenticated\IPs,
    Organization,
    Image,
};
use Innmind\SshKeyProvider\Provide;
use Ovh\Api;

function ovh(
    Api $api,
    Provide $provider,
    OperatingSystem $os
): Ark {
    $server = $os->control();

    return new Ark(
        new Forge\Ovh(
            $api,
            $available = new Forge\Ovh\Available\State($api),
            new Forge\Ovh\Bootstrap\Reinstall($api, $provider, $os->process()),
            new Forge\Ovh\Dispose\Stop($api, $os->process())
        ),
        new InstallationArray\Ovh($api, $available)
    );
}

function scaleway(
    Servers $servers,
    IPs $ips,
    CurrentProcess $process,
    Organization\Id $organization,
    Image\Id $image
): Ark {
    return new Ark(
        new Forge\Scaleway(
            $servers,
            $ips,
            $organization,
            $image,
            $process
        ),
        new InstallationArray\Scaleway($servers, $ips)
    );
}
