<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Closure;

final readonly class Route
{
    public function __construct(
        public Path $path,
        public Closure $handler
    ) {}
}
