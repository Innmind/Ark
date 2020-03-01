# Ark

[![Build Status](https://github.com/Innmind/Ark/workflows/CI/badge.svg)](https://github.com/Innmind/Ark/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/Ark/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Ark)
[![Type Coverage](https://shepherd.dev/github/Innmind/Ark/coverage.svg)](https://shepherd.dev/github/Innmind/Ark)

The ark is a simple tool to spinup new servers and trash the ones in service in your infrastructure.

It works with the [Ovh](https://ovh.com) api and uses the vps endpoints or with [Scaleway](https://www.scaleway.com/).

Only the installation provided by the ark array can be asked to be disposed.

### Ovh implementation detail

Since it's not possible to order new servers via the Ovh api this library rely on the state of your servers.

If a server is stopped it considers that it is available when asking for a new server. When a server is chosen as a new server it will reinstall the server with a [template id](https://eu.api.ovh.com/console/#/vps/%7BserviceName%7D/templates#GET) that you must provide, the only way to connect to the server will be via a ssh key (no password is generated as we couldn't automate the reinstallation).

With ovh disposing a server simply means it will stop the server (allowing it to be considered as available when asking for a new server).

## Installation

```sh
composer require innmind/ark
```

## Usage

### Scaleway

```php
use function Innmind\Ark\scaleway;
use function Innmind\ScalewaySdk\bootstrap as sdk;
use Innmind\OperatingSystem\Factory;
use Innmind\ScalewaySdk\{
    Token,
    Region,
    User,
    Organization,
    Image,
};
use Innmind\SshKeyProvider\Local;
use Innmind\Url\Path;

$os = Factory::build();
$sdk = sdk($os->remote()->http(), $os->clock());
$scaleway = $sdk->autnenticated(new Token\Id('uuid')); // create an api token at https://console.scaleway.com/account/credentials

$ark = scaleway(
    $scaleway->servers(Region::paris1()),
    $scaleway->ips(Region::paris1()),
    $scaleway->users(),
    $os->process(),
    new Local(
        $os->control()->processes(),
        Path::of('/home/{serverUser}/.ssh'),
    ),
    new User\Id('your user uuid'),
    new Organization\Id('the organization uuid you want to create servers in'),
    new Image\Id('the image uuid you want to build'),
);

$installation = $ark->forge()->new();
$installAppOn($installation->location());
$ark->array(); // will contain the new server now
```

### Ovh

The very first step is to buy vps servers from [ovh](https://www.ovh.com/fr/vps/), then you can start writing this kind of code:

```php
use function Innmind\Ark\ovh;
use Innmind\OperatingSystem\Factory;
use Innmind\SshKeyProvider\Local;
use Innmind\Url\Path;
use Ovh\Api;

$os = Factory::build();

$ark = ovh(
    new Api(/* args */),
    new Local(
        $os->control()->processes(),
        Path::of('/home/{serverUser}/.ssh'),
    ),
    $os,
);

$installation = $ark->forge()->new();
$installAppOn($installation->location());
$ark->array(); // will contain the new server now
```

You can refer to the [ovh documentation](https://api.ovh.com/g934.first_step_with_api) to know how you can generate the tokens needed to build the `Api` object.

**Important**: you need to generate the consumer key yourself as it can't be automated. The library requires the following access rights in order to work properly: `POST</me*>`, `DELETE</me*>`, `GET</vps*>` and `POST</vps*>`.
