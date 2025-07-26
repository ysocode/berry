<?php

declare(strict_types=1);

namespace YSOCode\Berry;

final readonly class Response
{
    public function __construct(
        public Status $status,
        public string $body = ''
    ) {}
}
