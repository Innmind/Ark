<?php
declare(strict_types = 1);

namespace Innmind\Ark\Forge;

use Innmind\Ark\{
    Forge,
    Installation,
};
use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Authenticated\IPs,
    Authenticated\Users,
    Organization,
    Image,
    Server,
    IP,
    User,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Period\Earth\Second;
use Innmind\Url\{
    Url,
    NullScheme,
};
use Innmind\SshKeyProvider\{
    Provide,
    PublicKey,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use Ramsey\Uuid\Uuid;

final class Scaleway implements Forge
{
    private $servers;
    private $ips;
    private $users;
    private $user;
    private $organization;
    private $image;
    private $process;
    private $provide;

    public function __construct(
        Servers $servers,
        IPs $ips,
        Users $users,
        User\Id $user,
        Organization\Id $organization,
        Image\Id $image,
        CurrentProcess $process,
        Provide $provide
    ) {
        $this->servers = $servers;
        $this->ips = $ips;
        $this->users = $users;
        $this->user = $user;
        $this->organization = $organization;
        $this->image = $image;
        $this->process = $process;
        $this->provide = $provide;
    }

    public function new(): Installation
    {
        $this->injectSshKeys();
        $ip = $this->generateIp();
        $server = $this->servers->create(
            new Server\Name((string) Uuid::uuid4()),
            $this->organization,
            $this->image,
            $ip->id()
        );
        $this->servers->execute(
            $server->id(),
            Server\Action::powerOn()
        );

        do {
            $this->process->halt(new Second(1));

            $server = $this->servers->get($server->id());
        } while ($server->state() !== Server\State::running());

        return new Installation(
            new Installation\Name((string) $server->id()),
            Url::fromString('ssh://root@'.$ip->address())->withScheme(new NullScheme)
        );
    }

    public function dispose(Installation $installation): void
    {
        $this->servers->execute(
            new Server\Id((string) $installation->name()),
            Server\Action::terminate()
        );
    }

    private function injectSshKeys(): void
    {
        $currentKeys = $this
            ->users
            ->get($this->user)
            ->sshKeys()
            ->reduce(
                Map::of('string', User\SshKey::class),
                static function(MapInterface $keys, User\SshKey $ssh): MapInterface {
                    return $keys->put(
                        $ssh->key(),
                        $ssh
                    );
                }
            );
        $keys = ($this->provide)()
            ->filter(static function(PublicKey $key) use ($currentKeys): bool {
                return !$currentKeys->contains((string) $key);
            })
            ->reduce(
                $currentKeys,
                static function(MapInterface $keys, PublicKey $key): MapInterface {
                    return $keys->put(
                        (string) $key,
                        new User\SshKey((string) $key)
                    );
                }
            )
            ->values();
        $this->users->updateSshKeys($this->user, ...$keys);
    }

    private function generateIp(): IP
    {
        $availableIps = $this
            ->ips
            ->list()
            ->filter(function(IP $ip): bool {
                return (string) $ip->organization() === (string) $this->organization &&
                    !$ip->attachedToAServer();
            });

        if (!$availableIps->empty()) {
            return $availableIps->current();
        }

        return $this->ips->create($this->organization);
    }
}
