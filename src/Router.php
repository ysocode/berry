<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Closure;
use LogicException;

final class Router
{
    /**
     * @var array<string, array<string, Route>>
     */
    private array $routes = [];

    /**
     * @var array<string, true>
     */
    private array $registeredPaths = [];

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
        if ($this->routeExists($method, $path)) {
            throw new LogicException(sprintf(
                'Route %s %s already exists.',
                $method->value,
                $path
            ));
        }

        $pathKey = (string) $path;

        $this->routes[$method->value][$pathKey] = new Route(
            $method,
            $path,
            $handler
        );

        $this->registeredPaths[$pathKey] = true;
    }

    private function routeExists(Method $method, Path $path): bool
    {
        $routes = $this->routes[$method->value] ?? null;
        if (! is_array($routes)) {
            return false;
        }

        return array_key_exists((string) $path, $routes);
    }

    public function match(Request $request): Route|Error
    {
        $method = $request->method;
        $path = $request->path;

        $pathKey = (string) $path;

        $route = $this->routes[$method->value][$pathKey] ?? null;

        if ($route instanceof Route) {
            return $route;
        }

        if ($this->registeredPaths[$pathKey] ?? false) {
            return new Error('Method not allowed.');
        }

        return new Error('Route not found.');
    }
}
