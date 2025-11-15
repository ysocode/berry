<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ResponseFactory
{
    public function createFromString(?string $body = null): Response
    {
        if (is_string($body)) {
            $body = new StreamFactory()->createFromString($body);
        }

        return new Response(HttpStatus::OK, body: $body);
    }
}
