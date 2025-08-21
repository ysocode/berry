<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Stringable;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;

final class Uri implements Stringable
{
    public private(set) Port $port;

    public bool $withDefaultPort = false;

    public function __construct(
        private(set) Scheme $scheme,
        private(set) Host $host,
        ?Port $port = null,
        private(set) ?Path $path = null,
        private(set) ?UserInfo $userInfo = null,
        private(set) ?Query $query = null,
        private(set) ?Fragment $fragment = null,
    ) {
        $this->port = $port ?? $scheme->getDefaultPort();
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->userInfo instanceof UserInfo) {
            $authority .= $this->userInfo.'@';
        }

        $authority .= $this->host;

        $isDefaultPort = $this->port->equals($this->scheme->getDefaultPort());

        if ($this->withDefaultPort || ! $isDefaultPort) {
            $authority .= ':'.$this->port->value;
        }

        return $authority;
    }

    public function withScheme(Scheme $scheme): self
    {
        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    public function withHost(Host $host): self
    {
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    public function withPort(Port $port): self
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function withPath(Path $path): self
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withUserInfo(UserInfo $userInfo): self
    {
        $new = clone $this;
        $new->userInfo = $userInfo;

        return $new;
    }

    public function withQuery(Query $query): self
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(Fragment $fragment): self
    {
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    public function __toString(): string
    {
        $uri = $this->scheme->value.'://'.$this->getAuthority();

        if ($this->path instanceof Path) {
            $uri .= $this->path;
        }

        if ($this->query instanceof Query) {
            $uri .= '?'.$this->query;
        }

        if ($this->fragment instanceof Fragment) {
            $uri .= '#'.$this->fragment;
        }

        return $uri;
    }
}
