<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Closure;

final readonly class Route
{
    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function __construct(
        public Method $method,
        public Path $path,
        public Handler|Closure $handler,
        public ?Name $name = null,
    ) {}
}
