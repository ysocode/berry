<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use NoDiscard;
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
    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withHeader(string $name, array $values): self
    {
        $header = new Header(new HeaderName($name), $values);

        $lowerHeaderName = strtolower((string) $header->name);
        $headers = $this->headers;
        $headers[$lowerHeaderName] = $header;

        return clone ($this, ['headers' => $headers]);
    }

    /**
     * @param  array<string>  $values
     */
    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withAddedHeader(string $name, array $values): self
    {
        $header = new Header(new HeaderName($name), $values);

        $lowerHeaderName = strtolower((string) $header->name);
        $headers = $this->headers;

        $mergedHeader = null;
        $currentHeader = $headers[$lowerHeaderName] ?? null;
        if ($currentHeader instanceof Header) {
            $mergedHeader = new Header($header->name, [...$currentHeader->values, ...$header->values]);
        }

        $headers[$lowerHeaderName] = $mergedHeader ?? $header;

        return clone ($this, ['headers' => $headers]);
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withoutHeader(string $name): self
    {
        $name = new HeaderName($name);
        $headers = $this->headers;

        $lowerHeaderName = strtolower((string) $name);
        unset($headers[$lowerHeaderName]);

        return clone ($this, ['headers' => $headers]);
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withBody(string $body): self
    {
        $stream = new StreamFactory()->createFromString($body);

        return clone ($this, ['body' => $stream]);
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withVersion(HttpVersion $version): self
    {
        return clone ($this, ['version' => $version]);
    }
}
