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
     * @var array<string, Route>
     */
    private array $namedRoutes = [];

    /**
     * @var array<string, true>
     */
    private array $registeredPaths = [];

    public function get(Path $path, Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::GET, $path, $handler, $name);
    }

    public function put(Path $path, Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::PUT, $path, $handler, $name);
    }

    public function post(Path $path, Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::POST, $path, $handler, $name);
    }

    public function delete(Path $path, Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::DELETE, $path, $handler, $name);
    }

    public function patch(Path $path, Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::PATCH, $path, $handler, $name);
    }

    private function addRoute(Method $method, Path $path, Closure $handler, ?Name $name = null): void
    {
        $pathKey = (string) $path;

        if (isset($this->routes[$method->value][$pathKey])) {
            throw new LogicException(sprintf(
                'Route %s %s already exists.',
                $method->value,
                $path
            ));
        }

        $route = new Route($method, $path, $handler, $name);

        $this->routes[$method->value][$pathKey] = $route;

        if ($name instanceof Name) {
            $nameKey = (string) $name;

            if (isset($this->namedRoutes[$nameKey])) {
                throw new LogicException(sprintf(
                    'Route name "%s" already exists.',
                    $name
                ));
            }

            $this->namedRoutes[$nameKey] = $route;
        }

        $this->registeredPaths[$pathKey] = true;
    }

    public function getMatchedRoute(Request $request): Route|Error
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

    public function getRouteByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }
}
