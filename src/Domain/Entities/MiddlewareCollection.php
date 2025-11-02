<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\Types\Middleware;

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

    /**
     * @param  array<Middleware>  $middlewares
     */
    public function addMiddlewares(array $middlewares): void
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
    }

    public function append(self $other): void
    {
        $this->addMiddlewares($other->middlewares);
    }

    public function clear(): void
    {
        $this->middlewares = [];
    }

    public function isNotEmpty(): bool
    {
        return $this->middlewares !== [];
    }
}
