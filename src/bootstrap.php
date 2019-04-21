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
    Authenticated\Users,
    Organization,
    Image,
    User,
};
use Innmind\SshKeyProvider\Provide;
use Ovh\Api;

function ovh(
    Api $api,
    Provide $provider,
    OperatingSystem $os
): Ark {
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
    Users $users,
    CurrentProcess $process,
    Provide $provide,
    User\Id $user,
    Organization\Id $organization,
    Image\Id $image
): Ark {
    return new Ark(
        new Forge\Scaleway(
            $servers,
            $ips,
            $users,
            $process,
            $provide,
            $user,
            $organization,
            $image
        ),
        new InstallationArray\Scaleway($servers, $ips)
    );
}
