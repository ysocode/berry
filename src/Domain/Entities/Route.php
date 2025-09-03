<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class Route
{
    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     * @param  array<MiddlewareInterface|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function __construct(
        private(set) HttpMethod $method,
        private(set) Path $path,
        private(set) RequestHandlerInterface|Closure $handler,
        private(set) ?Name $name = null,
        private(set) array $middlewares = []
    ) {}

    public function withMethod(HttpMethod $method): self
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function withPath(Path $path): self
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function withHandler(RequestHandlerInterface|Closure $handler): self
    {
        $new = clone $this;
        $new->handler = $handler;

        return $new;
    }

    public function withName(?Name $name): self
    {
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    /**
     * @param  MiddlewareInterface|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(MiddlewareInterface|Closure $middleware): void
    {
        $this->middlewares[] = $middleware;
    }
}
