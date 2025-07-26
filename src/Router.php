<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Closure;

final class Router
{
    /**
     * @var array<string, array<string, Route>>
     */
    private array $routes = [];

    public function get(Path $path, Closure $handler): void
    {
        $this->addRoute(Method::GET, $path, $handler);
    }

    public function put(Path $path, Closure $handler): void
    {
        $this->addRoute(Method::PUT, $path, $handler);
    }

    public function post(Path $path, Closure $handler): void
    {
        $this->addRoute(Method::POST, $path, $handler);
    }

    public function delete(Path $path, Closure $handler): void
    {
        $this->addRoute(Method::DELETE, $path, $handler);
    }

    public function patch(Path $path, Closure $handler): void
    {
        $this->addRoute(Method::PATCH, $path, $handler);
    }

    private function addRoute(Method $method, Path $path, Closure $handler): void
    {
        $this->routes[$method->value][(string) $path] = new Route(
            $path,
            $handler
        );
    }

    public function match(Request $request): ?Route
    {
        $method = $request->method;
        $path = $request->path;

        return $this->routes[$method->value][(string) $path] ?? null;
    }
}
