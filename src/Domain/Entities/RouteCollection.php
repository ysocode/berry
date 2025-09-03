<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;

final class RouteCollection
{
    /**
     * @var array<Route>
     */
    private array $routes = [];

    /**
     * @var array<string, int>
     */
    private array $routeIndexesByName = [];

    /**
     * @var array<string, int>
     */
    private array $routeIndexesByPath = [];

    public function addRoute(Route $route): void
    {
        if ($this->hasRouteByPath($route->path)) {
            throw new RuntimeException('Route path already exists.');
        }

        $this->routes[] = $route;

        $lastIndex = array_key_last($this->routes);

        $this->routeIndexesByPath[(string) $route->path] = $lastIndex;

        if ($route->name instanceof Name) {
            if ($this->hasRouteByName($route->name)) {
                throw new RuntimeException('Route name already exists.');
            }

            $this->routeIndexesByName[(string) $route->name] = $lastIndex;
        }
    }

    public function hasRouteByPath(Path $path): bool
    {
        return isset($this->routeIndexesByPath[(string) $path]);
    }

    public function getRouteByPath(Path $path): ?Route
    {
        $routeIndex = $this->routeIndexesByPath[(string) $path] ?? null;
        if (! is_int($routeIndex)) {
            return null;
        }

        return $this->routes[$routeIndex];
    }

    public function hasRouteByName(Name $name): bool
    {
        return isset($this->routeIndexesByName[(string) $name]);
    }

    public function getRouteByName(Name $name): ?Route
    {
        $routeIndex = $this->routeIndexesByName[(string) $name] ?? null;
        if (! is_int($routeIndex)) {
            return null;
        }

        return $this->routes[$routeIndex];
    }

    public function withName(Name $name): self
    {
        if ($this->routes === []) {
            throw new RuntimeException('No route to name.');
        }

        if ($this->hasRouteByName($name)) {
            throw new RuntimeException(sprintf('Route name "%s" already exists.', $name));
        }

        $lastIndex = array_key_last($this->routes);
        $route = $this->routes[$lastIndex];

        if ($route->name instanceof Name) {
            unset($this->routeIndexesByName[(string) $route->name]);
        }

        $this->routes[$lastIndex] = $route->withName($name);
        $this->routeIndexesByName[(string) $name] = $lastIndex;

        return $this;
    }
}
