<?php

declare(strict_types=1);

namespace YSOCode\Berry;

final readonly class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, string> $queryParams
     * @param array<string, string> $body
     */
    public function __construct(
        public Method $method,
        public Path $path,
        public array $headers = [],
        public array $queryParams = [],
        public array $body = []
    ) {}
}
