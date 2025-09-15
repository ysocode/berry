<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class RouteGroup
{
    public RouteRegistry $routeRegistry;

    public function __construct()
    {
        $this->routeRegistry = new RouteRegistry;
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::GET, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::PUT, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::POST, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::DELETE, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::PATCH, $path, $handler);
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        foreach ($this->routeRegistry->routeCollections as $routeCollection) {
            foreach ($routeCollection->routes as $route) {
                $route->addMiddleware($middleware);
            }
        }

        return $this;
    }
}
