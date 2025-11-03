<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

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

    public function withScheme(UriScheme $scheme): self
    {
        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    public function withHost(string $host): self
    {
        $host = new Host($host);

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    public function withPort(int $port): self
    {
        $port = new Port($port);

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function withPath(string $path): self
    {
        $path = new UriPath($path);

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): self
    {
        $userInfo = new UriUserInfo($user, $password);

        $new = clone $this;
        $new->userInfo = $userInfo;

        return $new;
    }

    public function withQuery(string $query): self
    {
        $query = new UriQuery($query);

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(string $fragment): self
    {
        $fragment = new UriFragment($fragment);

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
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
