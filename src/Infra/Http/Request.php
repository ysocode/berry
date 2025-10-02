<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
use YSOCode\Berry\Infra\Stream\Stream;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class Request
{
    use MessageTrait;
    use RequestTrait;

    /**
     * @param  array<Header>  $headers
     */
    public function __construct(
        HttpMethod $method,
        Uri $uri,
        array $headers = [],
        ?Stream $body = null,
        HttpVersion $version = new HttpVersion('1.1'),
    ) {
        $this->method = $method;

        $this->uri = $uri;

        $this->setTarget($uri);

        $this->body = $body ?? new StreamFactory()->createFromString();

        $this->setHeaders($headers);

        $this->version = $version;
    }
}
