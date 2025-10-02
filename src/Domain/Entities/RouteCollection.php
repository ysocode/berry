<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\RouteCollectionEvent;
use YSOCode\Berry\Domain\ValueObjects\RouteEvent;
use YSOCode\Berry\Domain\ValueObjects\UriPath;

final class RouteCollection
{
    /** @var array<string, array<Closure(array<string, mixed>): void>> */
    private array $listeners = [];

    /**
     * @var array<Route>
     */
    public private(set) array $routes = [];

    /**
     * @var array<string, int>
     */
    private array $routeIndexesByName = [];

    /**
     * @var array<string, int>
     */
    private array $routeIndexesByPath = [];

    /**
     * @param  Closure(array<string, mixed>): void  $listener
     */
    public function on(RouteCollectionEvent $event, Closure $listener): self
    {
        $this->listeners[$event->name][] = $listener;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emit(RouteCollectionEvent $event, array $data = []): void
    {
        foreach ($this->listeners[$event->name] ?? [] as $listener) {
            $listener($data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function setRouteIndexForName(Route $route, array $data): void
    {
        /** @var Name $name */
        $name = $data['name'];

        if ($this->hasRouteByName($name)) {
            throw new RuntimeException(sprintf('Route name "%s" already exists.', $name));
        }

        $this->emit(RouteCollectionEvent::ROUTE_NAME_CHANGED, ['routeName' => $name]);

        $this->routeIndexesByName[(string) $name] = $this->routeIndexesByPath[(string) $route->path];
    }

    public function addRoute(Route $route): self
    {
        if ($this->hasRouteByPath($route->path)) {
            throw new RuntimeException(sprintf('Route path "%s" already exists.', $route->path));
        }

        $route->on(RouteEvent::NAME_CHANGED, $this->setRouteIndexForName(...));

        $this->routes[] = $route;

        $lastIndex = array_key_last($this->routes);

        $this->routeIndexesByPath[(string) $route->path] = $lastIndex;

        if ($route->name instanceof Name) {
            if ($this->hasRouteByName($route->name)) {
                throw new RuntimeException(sprintf('Route name "%s" already exists.', $route->name));
            }

            $this->routeIndexesByName[(string) $route->name] = $lastIndex;
        }

        return $this;
    }

    public function hasRouteByPath(UriPath $path): bool
    {
        return isset($this->routeIndexesByPath[(string) $path]);
    }

    public function getRouteByPath(UriPath $path): ?Route
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
}
