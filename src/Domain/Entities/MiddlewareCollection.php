<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\ValueObjects\Middleware;

final class MiddlewareCollection
{
    /**
     * @var array<Middleware>
     */
    public private(set) array $middlewares = [];

    public function addMiddleware(Middleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }
}
