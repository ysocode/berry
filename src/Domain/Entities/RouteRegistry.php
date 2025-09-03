<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteRegistry
{
    /**
     * @var array<string, RouteCollection>
     */
    private array $routeCollections = [];

    private ?HttpMethod $lastUsedMethod = null;

    public function __construct()
    {
        foreach (HttpMethod::getValues() as $method) {
            $this->routeCollections[$method] = new RouteCollection;
        }
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function addRoute(HttpMethod $method, Path $path, RequestHandlerInterface|Closure $handler): void
    {
        $routeCollection = $this->routeCollections[$method->value];

        if ($routeCollection->hasRouteByPath($path)) {
            throw new RuntimeException(
                sprintf('Route %s %s already exists.', $method->value, $path)
            );
        }

        $routeCollection->addRoute(
            new Route($method, $path, $handler)
        );

        $this->lastUsedMethod = $method;
    }

    public function withName(Name $name): void
    {
        if (! $this->lastUsedMethod instanceof HttpMethod) {
            throw new RuntimeException('Define a route before calling withName().');
        }

        if ($this->hasRouteByName($name)) {
            throw new RuntimeException(sprintf('Route name "%s" already exists.', $name));
        }

        $this->routeCollections[$this->lastUsedMethod->value]->withName($name);
    }

    private function hasRouteByName(Name $name): bool
    {
        return array_any($this->routeCollections, fn ($routeCollection): bool => $routeCollection->hasRouteByName($name));
    }

    public function getRouteByName(Name $name): ?Route
    {
        foreach ($this->routeCollections as $routeCollection) {
            $route = $routeCollection->getRouteByName($name);
            if ($route instanceof Route) {
                return $route;
            }
        }

        return null;
    }

    public function getRouteByMethodAndPath(HttpMethod $method, Path $path): ?Route
    {
        $routeCollection = $this->routeCollections[$method->value];

        return $routeCollection->getRouteByPath($path);
    }

    public function hasRouteByPath(Path $path): bool
    {
        return array_any($this->routeCollections, fn ($routeCollection): bool => $routeCollection->hasRouteByPath($path));
    }
}
