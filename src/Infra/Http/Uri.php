<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use InvalidArgumentException;
use Stringable;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;

final class Uri implements Stringable
{
    public private(set) Scheme $scheme;

    public private(set) ?UserInfo $userInfo;

    public private(set) Host $host;

    public private(set) Port $port;

    public private(set) Path $path;

    public private(set) ?Query $query;

    public private(set) ?Fragment $fragment;

    public function __construct(string $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->extractParts($value);
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(string $value): true|Error
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return new Error('Invalid URI format.');
        }

        $parts = parse_url($value);
        if ($parts === false) {
            return new Error('Failed to parse URI.');
        }

        $scheme = $parts['scheme'] ?? null;
        if ($scheme === null || ($scheme === '' || $scheme === '0')) {
            return new Error('URI scheme is missing.');
        }

        $host = $parts['host'] ?? null;
        if ($host === null || ($host === '' || $host === '0')) {
            return new Error('URI host is missing.');
        }

        if (
            ! filter_var($host, FILTER_VALIDATE_IP) &&
            ! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
        ) {
            return new Error('Invalid URI host.');
        }

        return true;
    }

    private function extractParts(string $value): void
    {
        $parts = parse_url($value);
        if ($parts === false) {
            throw new InvalidArgumentException('Failed to parse URI.');
        }

        $scheme = $parts['scheme'] ?? null;
        if ($scheme === null) {
            throw new InvalidArgumentException('Scheme is required in URI.');
        }

        $this->scheme = Scheme::fromString($scheme);

        $userInfo = null;
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        if ($user !== null) {
            $userInfo = new UserInfo($user, $pass);
        }
        $this->userInfo = $userInfo;

        $host = $parts['host'] ?? null;
        if ($host === null) {
            throw new InvalidArgumentException('Host is required in URI.');
        }
        $this->host = new Host($host);

        $this->port = new Port($parts['port'] ?? $this->scheme->defaultPort());

        $this->path = new Path($parts['path'] ?? '/');

        $query = null;
        $partQuery = $parts['query'] ?? null;
        if ($partQuery !== null) {
            $query = new Query($partQuery);
        }
        $this->query = $query;

        $fragment = null;
        $partFragment = $parts['fragment'] ?? null;
        if ($partFragment !== null) {
            $fragment = new Fragment($partFragment);
        }
        $this->fragment = $fragment;
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->userInfo instanceof UserInfo) {
            $authority .= $this->userInfo.'@';
        }

        $authority .= $this->host;

        $defaultPort = new Port($this->scheme->defaultPort());
        if (! $this->port->equals($defaultPort)) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    public function withScheme(Scheme $scheme): self
    {
        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    public function withUserInfo(UserInfo $userInfo): self
    {
        $new = clone $this;
        $new->userInfo = $userInfo;

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
        $uri = $this->scheme->value.'://';

        $uri .= $this->getAuthority();

        $uri .= $this->path;

        if ($this->query instanceof Query && (string) $this->query !== '') {
            $uri .= '?'.$this->query;
        }

        if ($this->fragment instanceof Fragment && (string) $this->fragment !== '') {
            $uri .= '#'.$this->fragment;
        }

        return $uri;
    }
}
