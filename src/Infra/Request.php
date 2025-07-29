<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra;

use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Path;

final readonly class Request
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $queryParams
     * @param  array<string, string>  $body
     */
    public function __construct(
        public Method $method,
        public Path $path,
        public array $headers = [],
        public array $queryParams = [],
        public array $body = []
    ) {}
}
