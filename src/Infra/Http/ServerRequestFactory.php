<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use RuntimeException;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;
use YSOCode\Berry\Infra\Stream\Stream;

final class ServerRequestFactory
{
    private const array HEADERS_WITHOUT_HTTP_PREFIX = [
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'CONTENT_MD5',
    ];

    public function fromGlobals(): ServerRequest
    {
        /**
         * @var array<string, mixed> $_SERVER
         * @var array<string, string> $_COOKIE
         * @var array<string, string|array<int|string, mixed>> $_GET
         * @var array<string, string|array<int|string, mixed>> $_POST
         */
        return new ServerRequest(
            $this->getMethodFromGlobals(),
            new UriFactory()->createFromGlobals(),
            $this->getHeadersFromGlobals(),
            $this->getBodyFromGlobals(),
            $_SERVER,
            $_COOKIE,
            $_GET,
            $_POST,
            new UploadedFileFactory()->createFromGlobals()
        );
    }

    private function getMethodFromGlobals(): HttpMethod
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        if (! is_string($method)) {
            throw new RuntimeException('Unable to retrieve request method.');
        }

        return HttpMethod::from($method);
    }

    /**
     * @return array<Header>
     */
    private function getHeadersFromGlobals(): array
    {
        $headers = [];

        foreach ($_SERVER as $name => $values) {
            if (
                str_starts_with($name, 'HTTP_') ||
                str_starts_with($name, 'REDIRECT_') ||
                in_array($name, self::HEADERS_WITHOUT_HTTP_PREFIX)
            ) {
                if ($this->isShadowedByOriginalHeader($name)) {
                    continue;
                }

                $headerName = $this->normalizeHeaderName($name);

                if (! is_string($values)) {
                    throw new RuntimeException(
                        sprintf('Unable to retrieve header "%s" value.', $headerName)
                    );
                }

                $headers[] = new Header(
                    new HeaderName($headerName),
                    array_map(fn (string $value): string => trim($value), explode(',', $values)),
                );
            }
        }

        return $headers;
    }

    private function isShadowedByOriginalHeader(string $name): bool
    {
        return str_starts_with($name, 'REDIRECT_') && array_key_exists(substr($name, 9), $_SERVER);
    }

    private function normalizeHeaderName(string $name): string
    {
        $nameWithoutRedirect = str_starts_with($name, 'REDIRECT_')
            ? substr($name, 9)
            : $name;

        $nameWithoutHttp = str_starts_with($nameWithoutRedirect, 'HTTP_')
            ? substr($nameWithoutRedirect, 5)
            : $nameWithoutRedirect;

        $nameWithHyphens = str_replace('_', '-', $nameWithoutHttp);

        $parts = explode('-', $nameWithHyphens);
        $capitalizedParts = array_map(fn (string $part): string => ucfirst(strtolower($part)), $parts);

        return implode('-', $capitalizedParts);
    }

    private function getBodyFromGlobals(): Stream
    {
        $resource = fopen('php://input', 'rb');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open input stream.');
        }

        return new Stream(new StreamResource($resource));
    }
}
