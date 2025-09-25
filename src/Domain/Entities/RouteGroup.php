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

final class RouteGroup
{
    /**
     * @var array<Route>
     */
    public private(set) array $routes = [];

    public function __construct(
        private(set) ?Path $prefix = null
    ) {}

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(Path $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::GET, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(Path $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(Path $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::POST, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(Path $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(Path $path, string|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    private function addRoute(HttpMethod $method, Path $path, string|Closure $handler): Route
    {
        if ($this->prefix instanceof Path) {
            $path = $path->prepend($this->prefix);
        }

        $route = new Route($method, $path, $handler);

        $this->routes[] = $route;

        return $route;
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
