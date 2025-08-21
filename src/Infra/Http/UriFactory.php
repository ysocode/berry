<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use InvalidArgumentException;
use RuntimeException;
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
        if (! is_array($parts)) {
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

        $port = null;

        $partPort = $parts['port'] ?? null;
        if (is_int($partPort)) {
            $port = new Port($partPort);
        }

        $path = null;

        $partPath = $parts['path'] ?? null;
        if (is_string($partPath)) {
            $path = new Path($partPath);
        }

        $userInfo = null;

        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        if (is_string($user)) {
            $userInfo = new UserInfo($user, $pass);
        }

        $query = null;

        $partQuery = $parts['query'] ?? null;
        if (is_string($partQuery)) {
            $query = new Query($partQuery);
        }

        $fragment = null;

        $partFragment = $parts['fragment'] ?? null;
        if (is_string($partFragment)) {
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

    public function createFromGlobals(): Uri
    {
        [$host, $port] = $this->getHostAndPortFromGlobals();

        return new Uri(
            $this->getSchemeFromGlobals(),
            $host,
            $port,
            $this->getPathFromGlobals(),
            null,
            $this->getQueryFromGlobals(),
            null,
        );
    }

    /**
     * @return array{Host, Port}
     */
    private function getHostAndPortFromGlobals(): array
    {
        $httpHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
        if (! is_string($httpHost)) {
            throw new RuntimeException('Unable to retrieve http host.');
        }

        if (str_contains($httpHost, ':')) {
            [$extractedHost, $extractedPort] = explode(':', $httpHost, 2);

            return [new Host($extractedHost), new Port((int) $extractedPort)];
        }

        $serverPort = $_SERVER['SERVER_PORT'] ?? null;
        if (! is_int($serverPort)) {
            throw new RuntimeException('Unable to retrieve server port.');
        }

        return [new Host($httpHost), new Port($serverPort)];
    }

    private function getSchemeFromGlobals(): Scheme
    {
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? null;
        if (! is_string($requestScheme)) {
            throw new RuntimeException('Unable to retrieve request scheme.');
        }

        return Scheme::from($requestScheme);
    }

    private function getPathFromGlobals(): ?Path
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? null;
        if (! is_string($requestUri)) {
            throw new RuntimeException('Unable to retrieve request URI.');
        }

        $parts = parse_url($requestUri);

        $partPath = $parts['path'] ?? null;
        if (! is_string($partPath)) {
            return null;
        }

        return new Path($partPath);
    }

    private function getQueryFromGlobals(): ?Query
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? null;
        if (! is_string($queryString)) {
            return null;
        }

        return new Query($queryString);
    }
}
