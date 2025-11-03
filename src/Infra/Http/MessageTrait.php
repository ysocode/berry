<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\Enums\HttpVersion;
use YSOCode\Berry\Domain\Types\Header;
use YSOCode\Berry\Domain\Types\HeaderName;
use YSOCode\Berry\Infra\Stream\Stream;
use YSOCode\Berry\Infra\Stream\StreamFactory;

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

    public function hasHeader(string $name): bool
    {
        $name = new HeaderName($name);

        $lowerHeaderName = strtolower((string) $name);

        return isset($this->headers[$lowerHeaderName]);
    }

    public function getHeader(string $name): ?Header
    {
        $name = new HeaderName($name);

        $lowerHeaderName = strtolower((string) $name);

        return $this->headers[$lowerHeaderName] ?? null;
    }

    /**
     * @param  array<string>  $values
     */
    public function withHeader(string $name, array $values): self
    {
        $header = new Header(new HeaderName($name), $values);

        $new = clone $this;

        $lowerHeaderName = strtolower((string) $header->name);

        $new->headers[$lowerHeaderName] = $header;

        return $new;
    }

    /**
     * @param  array<string>  $values
     */
    public function withAddedHeader(string $name, array $values): self
    {
        $header = new Header(new HeaderName($name), $values);

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

    public function withoutHeader(string $name): self
    {
        $name = new HeaderName($name);

        $new = clone $this;

        $lowerHeaderName = strtolower((string) $name);

        unset($new->headers[$lowerHeaderName]);

        return $new;
    }

    public function withBody(string $body): self
    {
        $stream = new StreamFactory()->createFromString($body);

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
