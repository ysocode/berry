<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Stringable;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\UriFragment;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Domain\ValueObjects\UriQuery;
use YSOCode\Berry\Domain\ValueObjects\UriScheme;
use YSOCode\Berry\Domain\ValueObjects\UriUserInfo;

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

    public function withPath(UriPath $path): self
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withUserInfo(UriUserInfo $userInfo): self
    {
        $new = clone $this;
        $new->userInfo = $userInfo;

        return $new;
    }

    public function withQuery(UriQuery $query): self
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(UriFragment $fragment): self
    {
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
