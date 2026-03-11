<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use NoDiscard;
use Stringable;
use YSOCode\Berry\Domain\Enums\UriScheme;
use YSOCode\Berry\Domain\Types\Host;
use YSOCode\Berry\Domain\Types\Port;
use YSOCode\Berry\Domain\Types\UriFragment;
use YSOCode\Berry\Domain\Types\UriPath;
use YSOCode\Berry\Domain\Types\UriQuery;
use YSOCode\Berry\Domain\Types\UriUserInfo;

final class Uri implements Stringable
{
    public private(set) Port $port;

    public bool $withDefaultPort = false;

    public function __construct(
        private(set) UriScheme $scheme,
        private(set) Host $host,
        ?Port $port = null,
        private(set) ?UriPath $path = null,
        private(set) ?UriUserInfo $userInfo = null,
        private(set) ?UriQuery $query = null,
        private(set) ?UriFragment $fragment = null,
    ) {
        $this->port = $port ?? $scheme->getDefaultPort();
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->userInfo instanceof UriUserInfo) {
            $authority .= $this->userInfo.'@';
        }

        $authority .= $this->host;

        $isDefaultPort = $this->port->equals($this->scheme->getDefaultPort());

        if ($this->withDefaultPort || ! $isDefaultPort) {
            $authority .= ':'.$this->port->value;
        }

        return $authority;
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withScheme(UriScheme $scheme): self
    {
        return clone ($this, ['scheme' => $scheme]);
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withHost(string $host): self
    {
        $host = new Host($host);

        return clone ($this, ['host' => $host]);
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withPort(int $port): self
    {
        $port = new Port($port);

        return clone ($this, ['port' => $port]);
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withPath(string $path): self
    {
        $path = new UriPath($path);

        return clone ($this, ['path' => $path]);
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withUserInfo(string $user, ?string $password = null): self
    {
        $userInfo = new UriUserInfo($user, $password);

        return clone ($this, ['userInfo' => $userInfo]);
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withQuery(string $query): self
    {
        $query = new UriQuery($query);

        return clone ($this, ['query' => $query]);
    }

    #[NoDiscard('Uri instances are immutable; use the returned clone.')]
    public function withFragment(string $fragment): self
    {
        $fragment = new UriFragment($fragment);

        return clone ($this, ['fragment' => $fragment]);
    }

    public function __toString(): string
    {
        $uri = $this->scheme->value.'://'.$this->getAuthority();

        if ($this->path instanceof UriPath) {
            $uri .= $this->path;
        }

        if ($this->query instanceof UriQuery) {
            $uri .= '?'.$this->query;
        }

        if ($this->fragment instanceof UriFragment) {
            $uri .= '#'.$this->fragment;
        }

        return $uri;
    }
}
