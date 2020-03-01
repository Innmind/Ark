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
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Url\Url;
use Innmind\SshKeyProvider\{
    Provide,
    PublicKey,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\{
    unwrap,
    first,
};
use Ramsey\Uuid\Uuid;

final class Scaleway implements Forge
{
    private Servers $servers;
    private IPs $ips;
    private Users $users;
    private User\Id $user;
    private Organization\Id $organization;
    private Image\Id $image;
    private CurrentProcess $process;
    private Provide $provide;

    public function __construct(
        Servers $servers,
        IPs $ips,
        Users $users,
        CurrentProcess $process,
        Provide $provide,
        User\Id $user,
        Organization\Id $organization,
        Image\Id $image
    ) {
        $this->servers = $servers;
        $this->ips = $ips;
        $this->users = $users;
        $this->process = $process;
        $this->provide = $provide;
        $this->user = $user;
        $this->organization = $organization;
        $this->image = $image;
    }

    public function new(): Installation
    {
        $this->injectSshKeys();
        $ip = $this->generateIp();
        $server = $this->servers->create(
            new Server\Name(Uuid::uuid4()->toString()),
            $this->organization,
            $this->image,
            $ip->id(),
        );
        $this->servers->execute(
            $server->id(),
            Server\Action::powerOn(),
        );

        do {
            $this->process->halt(new Second(1));

            $server = $this->servers->get($server->id());
        } while ($server->state() !== Server\State::running());

        return new Installation(
            new Installation\Name($server->id()->toString()),
            Url::of('ssh://root@'.$ip->address()->toString())->withoutScheme(),
        );
    }

    public function dispose(Installation $installation): void
    {
        $this->servers->execute(
            new Server\Id($installation->name()->toString()),
            Server\Action::terminate(),
        );
    }

    private function injectSshKeys(): void
    {
        $currentKeys = $this
            ->users
            ->get($this->user)
            ->sshKeys()
            ->toMapOf(
                'string',
                User\SshKey::class,
                static function(User\SshKey $ssh): \Generator {
                    yield $ssh->key() => $ssh;
                },
            );
        $newKeys = ($this->provide)()
            ->filter(static function(PublicKey $key) use ($currentKeys): bool {
                return !$currentKeys->contains($key->toString());
            })
            ->toMapOf(
                'string',
                User\SshKey::class,
                static function(PublicKey $key): \Generator {
                    yield $key->toString() => new User\SshKey(
                        $key->toString(),
                    );
                },
            );
        $keys = $currentKeys
            ->merge($newKeys)
            ->values();
        $this->users->updateSshKeys($this->user, ...unwrap($keys));
    }

    private function generateIp(): IP
    {
        $availableIps = $this
            ->ips
            ->list()
            ->filter(function(IP $ip): bool {
                return $ip->organization()->toString() === $this->organization->toString() &&
                    !$ip->attachedToAServer();
            });

        if (!$availableIps->empty()) {
            return first($availableIps);
        }

        return $this->ips->create($this->organization);
    }
}
