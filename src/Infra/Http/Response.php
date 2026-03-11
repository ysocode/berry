<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use NoDiscard;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Enums\HttpVersion;
use YSOCode\Berry\Domain\Types\Header;
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
        array $headers = [],
        ?Stream $body = null,
        HttpVersion $version = HttpVersion::V1_1,
    ) {
        $this->body = $body ?? new StreamFactory()->createFromString();

        $this->setHeaders($headers);

        $this->version = $version;
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withStatus(HttpStatus $status): self
    {
        return clone ($this, ['status' => $status]);
    }
}
