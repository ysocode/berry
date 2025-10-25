<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Payloads;

use YSOCode\Berry\Domain\Entities\Route;

final readonly class ResolvedRoute
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public Route $route,
        public array $parameters,
    ) {}
}
