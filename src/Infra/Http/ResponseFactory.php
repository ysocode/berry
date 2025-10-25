<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ResponseFactory
{
    public function fromBody(?string $body = null): Response
    {
        if (is_string($body)) {
            $body = new StreamFactory()->createFromString($body);
        }

        return new Response(HttpStatus::OK, body: $body);
    }

    public function fromError(Error $error): Response
    {
        return match (true) {
            $error->equals(new Error('Method not allowed.')) => new Response(HttpStatus::METHOD_NOT_ALLOWED),
            $error->equals(new Error('Route not found.')) => new Response(HttpStatus::NOT_FOUND),
            default => new Response(HttpStatus::INTERNAL_SERVER_ERROR),
        };
    }
}
