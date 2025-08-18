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
        private(set) HttpStatus $status,
        array $headers,
        ?Stream $body = null,
        HttpVersion $version = new HttpVersion('1.1'),
    ) {
        $this->body = $body ?? new StreamFactory()->createFromString();

        $this->setHeaders($headers);

        $this->version = $version;
    }

    public function withStatus(HttpStatus $status): self
    {
        $new = clone $this;
        $new->status = $status;

        return $new;
    }
}
