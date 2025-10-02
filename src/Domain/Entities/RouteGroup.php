<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteGroup
{
    /**
     * @var array<Route>
     */
    public private(set) array $routes = [];

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(UriPath $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::GET, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(UriPath $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(UriPath $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::POST, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(UriPath $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(UriPath $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    private function addRoute(HttpMethod $method, UriPath $path, string|Closure $handler): Route
    {
        $route = new Route($method, $path, $handler);

        $this->routes[] = $route;

        return $route;
    }

    public function addPrefix(UriPath $prefix): self
    {
        foreach ($this->routes as $route) {
            $route->addPrefix($prefix);
        }

        return $this;
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        foreach ($this->routes as $route) {
            $route->addMiddleware($middleware);
        }

        return $this;
    }
}
