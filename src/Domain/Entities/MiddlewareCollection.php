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

    public function appendMiddleware(Middleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function prependMiddleware(Middleware $middleware): void
    {
        array_unshift($this->middlewares, $middleware);
    }

    /**
     * @param  array<Middleware>  $middlewares
     */
    public function appendMiddlewares(array $middlewares): void
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
    }

    /**
     * @param  array<Middleware>  $middlewares
     */
    public function prependMiddlewares(array $middlewares): void
    {
        $this->middlewares = array_merge($middlewares, $this->middlewares);
    }

    public function append(self $other): void
    {
        $this->appendMiddlewares($other->middlewares);
    }

    public function prepend(self $other): void
    {
        $this->prependMiddlewares($other->middlewares);
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
