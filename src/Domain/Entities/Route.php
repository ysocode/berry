<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\RouteEvent;
use YSOCode\Berry\Domain\Support\EventTrait;
use YSOCode\Berry\Domain\ValueObjects\Middleware;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RouteName;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
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
        $this->emit(RouteEvent::NAME_CHANGED, ['name' => new RouteName($name)]);

        $this->name = new RouteName($name);

        return $this;
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest, RequestHandlerInterface): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        $this->middlewareCollection->addMiddleware(new Middleware($middleware));

        return $this;
    }

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest, RequestHandlerInterface): Response>  $middlewares
     */
    public function addMiddlewares(array $middlewares): self
    {
        $this->middlewareCollection->addMiddlewares(
            array_map(fn (string|Closure $middleware): Middleware => new Middleware($middleware), $middlewares)
        );

        return $this;
    }

    public function addPrefix(RoutePathPattern $pathPattern): self
    {
        $this->pathPattern = $this->pathPattern->prepend($pathPattern);

        return $this;
    }
}
