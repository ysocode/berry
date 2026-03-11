<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use InvalidArgumentException;
use RuntimeException;
use Uri\Rfc3986\Uri as NativeUri;
use YSOCode\Berry\Domain\Enums\UriScheme;
use YSOCode\Berry\Domain\Types\Host;
use YSOCode\Berry\Domain\Types\Port;
use YSOCode\Berry\Domain\Types\UriFragment;
use YSOCode\Berry\Domain\Types\UriPath;
use YSOCode\Berry\Domain\Types\UriQuery;
use YSOCode\Berry\Domain\Types\UriUserInfo;

final readonly class UriFactory
{
    public function createFromString(string $uri): Uri
    {
        $nativeUri = NativeUri::parse($uri);
        if (! $nativeUri instanceof NativeUri) {
            throw new InvalidArgumentException('Failed to parse URL.');
        }

        $scheme = $nativeUri->getScheme();
        if (! is_string($scheme) || $scheme === '') {
            throw new InvalidArgumentException('URL scheme is missing.');
        }

        $scheme = UriScheme::from($scheme);

        $host = $nativeUri->getHost();
        if (! is_string($host) || $host === '') {
            throw new InvalidArgumentException('URL host is missing.');
        }

        $host = new Host($host);

        $port = $nativeUri->getPort();
        $path = $nativeUri->getPath();
        $username = $nativeUri->getUsername();
        $password = $nativeUri->getPassword();
        $query = $nativeUri->getQuery();
        $fragment = $nativeUri->getFragment();

        return new Uri(
            $scheme,
            $host,
            $port ? new Port($port) : null,
            $path !== '' && $path !== '0' ? new UriPath($path) : null,
            $username ? new UriUserInfo($username, $password) : null,
            $query ? new UriQuery($query) : null,
            $fragment ? new UriFragment($fragment) : null,
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
        );
    }

    /**
     * @return array{Host, Port}
     */
    private function getHostAndPortFromGlobals(): array
    {
        $httpHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
        if (! is_string($httpHost) || $httpHost === '') {
            throw new RuntimeException('Unable to retrieve http host.');
        }

        $nativeUri = NativeUri::parse('//'.$httpHost);
        if (! $nativeUri instanceof NativeUri) {
            throw new RuntimeException('Unable to parse http host.');
        }

        $extractedHost = $nativeUri->getHost();
        if (! is_string($extractedHost) || $extractedHost === '') {
            throw new RuntimeException('Unable to retrieve http host.');
        }

        $extractedPort = $nativeUri->getPort();
        if (! is_int($extractedPort)) {
            $serverPort = $_SERVER['SERVER_PORT'] ?? null;
            if (! is_int($serverPort)) {
                throw new RuntimeException('Unable to retrieve server port.');
            }
        }

        return [new Host($extractedHost), new Port($extractedPort ?? $serverPort)];
    }

    private function getSchemeFromGlobals(): UriScheme
    {
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? null;
        if (! is_string($requestScheme) || $requestScheme === '') {
            throw new RuntimeException('Unable to retrieve request scheme.');
        }

        return UriScheme::from($requestScheme);
    }

    private function getPathFromGlobals(): ?UriPath
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? null;
        if (! is_string($requestUri) || $requestUri === '') {
            throw new RuntimeException('Unable to retrieve request URI.');
        }

        $nativeUri = NativeUri::parse($requestUri);
        if (! $nativeUri instanceof NativeUri) {
            throw new RuntimeException('Unable to parse request URI.');
        }

        $partPath = $nativeUri->getPath();
        if ($partPath === '' || $partPath === '0') {
            return null;
        }

        return new UriPath($partPath);
    }

    private function getQueryFromGlobals(): ?UriQuery
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? null;
        if (! is_string($queryString) || $queryString === '') {
            return null;
        }

        return new UriQuery($queryString);
    }
}
