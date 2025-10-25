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
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::GET, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::PUT, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::POST, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::DELETE, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::PATCH, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function head(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::HEAD, new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function options(string $pathPattern, string|Closure $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::OPTIONS, new RoutePathPattern($pathPattern), new RequestHandler($handler));
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
        foreach ($middlewares as $middleware) {
            $this->middlewareCollection->addMiddleware(new Middleware($middleware));
        }

        return $this;
    }
}
