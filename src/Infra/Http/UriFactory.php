<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use InvalidArgumentException;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;

final readonly class UriFactory
{
    public function createFromString(string $uri): Uri
    {
        if (! filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format.');
        }

        $parts = parse_url($uri);
        if ($parts === false) {
            throw new InvalidArgumentException('Failed to parse URL.');
        }

        $scheme = $parts['scheme'] ?? null;
        if (! is_string($scheme)) {
            throw new InvalidArgumentException('URL scheme is missing.');
        }

        $scheme = Scheme::from($scheme);

        $host = $parts['host'] ?? null;
        if (! is_string($host)) {
            throw new InvalidArgumentException('URL host is missing.');
        }

        $host = new Host($host);

        $port = new Port($parts['port'] ?? $scheme->defaultPort());

        $path = null;

        $partPath = $parts['path'] ?? null;
        if ($partPath !== null) {
            $path = new Path($partPath);
        }

        $userInfo = null;

        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        if ($user !== null) {
            $userInfo = new UserInfo($pass !== null ? "{$user}:{$pass}" : $user);
        }

        $query = null;

        $partQuery = $parts['query'] ?? null;
        if ($partQuery !== null) {
            $query = new Query($partQuery);
        }

        $fragment = null;

        $partFragment = $parts['fragment'] ?? null;
        if ($partFragment !== null) {
            $fragment = new Fragment($partFragment);
        }

        return new Uri(
            $scheme,
            $host,
            $port,
            $path,
            $userInfo,
            $query,
            $fragment,
        );
    }
}
