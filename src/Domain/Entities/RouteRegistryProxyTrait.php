<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Types\Middleware;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RoutePathPattern;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

trait RouteRegistryProxyTrait
{
    public readonly RouteRegistry $routeRegistry;

    private readonly MiddlewareCollection $middlewareCollection;

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function get(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::GET, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function put(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::PUT, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function post(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::POST, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::DELETE, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::PATCH, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function head(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::HEAD, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function options(string $pathPattern, string|RequestHandlerInterface|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::OPTIONS, new RoutePathPattern($pathPattern), new RequestHandler($handler));
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
     * @param  class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response  $middleware
     */
    public function prependMiddleware(string|MiddlewareInterface|Closure $middleware): self
    {
        $this->middlewareCollection->prependMiddleware(new Middleware($middleware));

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

    /**
     * @param  array<class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response>  $middlewares
     */
    public function prependMiddlewares(array $middlewares): self
    {
        $this->middlewareCollection->prependMiddlewares(
            array_map(fn (string|MiddlewareInterface|Closure $middleware): Middleware => new Middleware($middleware), $middlewares)
        );

        return $this;
    }
}
