<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\RouteEvent;
use YSOCode\Berry\Domain\Traits\EventTrait;
use YSOCode\Berry\Domain\Types\Middleware;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RouteName;
use YSOCode\Berry\Domain\Types\RoutePathPattern;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class Route
{
    /** @use EventTrait<self, RouteEvent> */
    use EventTrait;

    public function __construct(
        public readonly HttpMethod $method,
        private(set) RoutePathPattern $pathPattern,
        public readonly RequestHandler $handler,
        private(set) ?RouteName $name = null,
        public readonly MiddlewareCollection $middlewareCollection = new MiddlewareCollection
    ) {}

    public function setName(string $name): self
    {
        $name = new RouteName($name);
        $this->emit(RouteEvent::NAME_CHANGED, ['name' => $name]);

        $this->name = $name;

        return $this;
    }

    /**
     * @param  class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response  $middleware
     */
    public function appendMiddleware(string|MiddlewareInterface|Closure $middleware): self
    {
        $this->middlewareCollection->appendMiddleware(new Middleware($middleware));

        return $this;
    }

    /**
     * @param  array<class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response>  $middlewares
     */
    public function appendMiddlewares(array $middlewares): self
    {
        $this->middlewareCollection->appendMiddlewares(
            array_map(fn (string|MiddlewareInterface|Closure $middleware): Middleware => new Middleware($middleware), $middlewares)
        );

        return $this;
    }

    public function addPrefix(string $pathPattern): self
    {
        $this->pathPattern = $this->pathPattern->prepend(new RoutePathPattern($pathPattern));

        return $this;
    }
}
