<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use RuntimeException;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\RouteCollectionEvent;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RouteName;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Domain\ValueObjects\UriPath;

final class RouteRegistry
{
    /** @var array<string, RouteCollection> */
    private array $routeCollectionsByMethod = [];

    public function __construct()
    {
        foreach (HttpMethod::cases() as $method) {
            $routeCollection = new RouteCollection;
            $routeCollection->on(RouteCollectionEvent::ROUTE_NAME_CHANGED, $this->assertUniqueName(...));

            $this->routeCollectionsByMethod[$method->value] = $routeCollection;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertUniqueName(RouteCollection $routeCollection, array $data): void
    {
        $name = $data['name'] ?? null;
        if (! $name instanceof RouteName) {
            throw new RuntimeException('Route name should be an instance of RouteName.');
        }

        $otherRouteCollections = array_filter(
            $this->routeCollectionsByMethod,
            fn (RouteCollection $currentRouteCollection): bool => $currentRouteCollection !== $routeCollection
        );

        foreach ($otherRouteCollections as $routeCollection) {
            if ($routeCollection->hasRouteByName($name)) {
                throw new RuntimeException(sprintf('Route name "%s" already exists.', $name));
            }
        }
    }

    public function hasRouteByName(RouteName $name): bool
    {
        foreach ($this->routeCollectionsByMethod as $routeCollection) {
            if ($routeCollection->hasRouteByName($name)) {
                return true;
            }
        }

        return false;
    }

    public function map(HttpMethod $method, RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        $route = new Route($method, $pathPattern, $handler);

        $this->routeCollectionsByMethod[$method->value]->addRoute($route);

        return $route;
    }

    public function getRouteByMethodAndPath(HttpMethod $method, UriPath $path): ResolvedRoute|Error
    {
        $resolvedRoute = $this->routeCollectionsByMethod[$method->value]->getRouteByPath($path);
        if (! $resolvedRoute instanceof ResolvedRoute) {
            $otherRouteCollections = array_filter(
                $this->routeCollectionsByMethod,
                fn (string $currentMethod): bool => HttpMethod::from($currentMethod) !== $method,
                ARRAY_FILTER_USE_KEY
            );

            foreach ($otherRouteCollections as $routeCollection) {
                if ($routeCollection->hasRouteByPath($path)) {
                    return new Error('Method not allowed.');
                }
            }

            return new Error('Route not found.');
        }

        return $resolvedRoute;
    }

    public function append(self $other): void
    {
        foreach ($other->routeCollectionsByMethod as $method => $routeCollection) {
            $this->routeCollectionsByMethod[$method]->append($routeCollection);
        }
    }

    /**
     * @return array<Route>
     */
    public function getRoutes(): array
    {
        $collectedRoutes = [];

        foreach ($this->routeCollectionsByMethod as $routeCollection) {
            $collectedRoutes = array_merge($collectedRoutes, $routeCollection->getRoutes());
        }

        return $collectedRoutes;
    }
}
