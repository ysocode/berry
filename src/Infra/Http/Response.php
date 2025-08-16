<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
use YSOCode\Berry\Infra\Stream\Stream;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class Response
{
    use MessageTrait;

    /**
     * @param  array<Header>  $headers
     */
    public function __construct(
        HttpStatus $status,
        array $headers,
        ?Stream $body = null,
        HttpVersion $version = new HttpVersion('1.1'),
    ) {
        $this->status = $status;
        $this->body = $body ?? new StreamFactory()->createFromString();

        $this->setHeaders($headers);

        $this->version = $version;
    }

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
}
