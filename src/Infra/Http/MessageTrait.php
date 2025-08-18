<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
use YSOCode\Berry\Infra\Stream\Stream;

trait MessageTrait
{
    /**
     * @var array<string, Header>
     */
    public private(set) array $headers = [];

    public private(set) Stream $body;

    public private(set) HttpVersion $version;

    /**
     * @param  array<Header>  $headers
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            $lowerHeaderName = strtolower((string) $header->name);
            $this->headers[$lowerHeaderName] = $header;
        }
    }

    public function hasHeader(HeaderName $name): bool
    {
        $lowerHeaderName = strtolower((string) $name);

        return isset($this->headers[$lowerHeaderName]);
    }

    public function getHeader(HeaderName $name): ?Header
    {
        $lowerHeaderName = strtolower((string) $name);

        return $this->headers[$lowerHeaderName] ?? null;
    }

    public function withHeader(Header $header): self
    {
        $new = clone $this;

        $lowerHeaderName = strtolower((string) $header->name);

        $new->headers[$lowerHeaderName] = $header;

        return $new;
    }

    public function withAddedHeader(Header $header): self
    {
        $new = clone $this;

        $lowerHeaderName = strtolower((string) $header->name);

        $mergedHeader = null;

        $currentHeader = $new->headers[$lowerHeaderName] ?? null;
        if ($currentHeader instanceof Header) {
            $mergedHeader = new Header($header->name, [...$currentHeader->values, ...$header->values]);
        }

        $new->headers[$lowerHeaderName] = $mergedHeader ?? $header;

        return $new;
    }

    public function withoutHeader(HeaderName $name): self
    {
        $new = clone $this;

        $lowerHeaderName = strtolower((string) $name);

        unset($new->headers[$lowerHeaderName]);

        return $new;
    }

    public function withBody(Stream $stream): self
    {
        $new = clone $this;
        $new->body = $stream;

        return $new;
    }

    public function withVersion(HttpVersion $version): self
    {
        $new = clone $this;
        $new->version = $version;

        return $new;
    }
}
