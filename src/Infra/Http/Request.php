<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use InvalidArgumentException;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Infra\Stream\Stream;

final class Request
{
    /**
     * @var array<string, Header>
     */
    private array $headers = [];

    /**
     * @var array<string, string>
     */
    private array $headerNames = [];

    /**
     * @param  array<Header>  $headers
     */
    public function __construct(
        private(set) Method $method,
        private(set) Uri $uri,
        array $headers = [],
        private(set) ?Stream $body = null,
    ) {
        $this->setHeaders($headers);
    }

    /**
     * @param  array<Header>  $headers
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            if (! $header instanceof Header) {
                throw new InvalidArgumentException('Each header must be an instance of Header.');
            }

            $normalizedName = strtolower((string) $header->name);
            $this->headerNames[$normalizedName] = (string) $header->name;

            $this->headers[(string) $header->name] = $header;
        }
    }

    public function getHeader(HeaderName $name): ?Header
    {
        $normalizedHeaderName = strtolower((string) $name);
        $currentHeaderName = $this->headerNames[$normalizedHeaderName] ?? null;

        if ($currentHeaderName === null) {
            return null;
        }

        return $this->headers[$currentHeaderName] ?? null;
    }

    public function hasHeader(HeaderName $name): bool
    {
        $normalizedHeaderName = strtolower((string) $name);
        $currentHeaderName = $this->headerNames[$normalizedHeaderName] ?? null;

        return $currentHeaderName !== null && isset($this->headers[$currentHeaderName]);
    }

    public function withHeader(Header $header): self
    {
        $normalizedHeaderName = strtolower((string) $header->name);
        $currentHeaderName = $this->headerNames[$normalizedHeaderName] ?? null;

        if ($currentHeaderName !== null) {
            $currentHeader = $this->headers[$currentHeaderName] ?? null;
            if ($currentHeader?->equals($header)) {
                return $this;
            }
        }

        $new = clone $this;

        unset($new->headers[$currentHeaderName]);

        $new->headerNames[$normalizedHeaderName] = (string) $header->name;
        $new->headers[(string) $header->name] = $header;

        return $new;
    }

    public function withoutHeader(HeaderName $name): self
    {
        $normalizedName = strtolower((string) $name);
        $currentName = $this->headerNames[$normalizedName] ?? null;

        if ($currentName === null || ! isset($this->headers[$currentName])) {
            return $this;
        }

        $new = clone $this;

        unset($new->headerNames[$normalizedName], $new->headers[$currentName]);

        return $new;
    }

    public function withBody(Stream $body): self
    {
        if ($this->body === $body) {
            return $this;
        }

        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    public function withMethod(Method $method): self
    {
        if ($this->method === $method) {
            return $this;
        }

        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function withUri(Uri $uri): self
    {
        if ($this->uri === $uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        return $new;
    }
}
