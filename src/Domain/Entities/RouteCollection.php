<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use RuntimeException;
use YSOCode\Berry\Domain\Enums\RouteCollectionEvent;
use YSOCode\Berry\Domain\Enums\RouteEvent;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Traits\EventTrait;
use YSOCode\Berry\Domain\Types\RouteName;
use YSOCode\Berry\Domain\Types\UriPath;

/**
 * @phpstan-type Node array{children: array<string, mixed>, route: ?Route}
 */
final class RouteCollection
{
    /** @use EventTrait<self, RouteCollectionEvent> */
    use EventTrait;

    /**
     * @var array<string, Node>
     */
    private array $routesBySegment = [];

    /**
     * @var array<string, Route>
     */
    private array $routesByName = [];

    public function addRoute(Route $route): void
    {
        $segments = $route->pathPattern->getSegments();
        $lastIndex = array_key_last($segments);

        $tree = &$this->routesBySegment;

        foreach ($segments as $index => $segment) {
            $tree[$segment] ??= ['children' => [], 'route' => null];

            if ($index === $lastIndex) {
                if (isset($tree[$segment]['route'])) {
                    throw new RuntimeException("Route conflict: {$route->pathPattern}.");
                }

                $route->on(
                    RouteEvent::NAME_CHANGED,
                    $this->setRouteByName(...)
                );

                if ($route->name instanceof RouteName) {
                    $this->routesByName[(string) $route->name] = $route;
                }

                $tree[$segment]['route'] = $route;
            }

            /** @var array<string, Node> $tree */
            $tree = &$tree[$segment]['children'];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function setRouteByName(Route $route, array $data): void
    {
        $name = $data['name'] ?? null;
        if (! $name instanceof RouteName) {
            throw new RuntimeException('Route name should be an instance of RouteName.');
        }

        $this->emit(RouteCollectionEvent::ROUTE_NAME_CHANGED, ['name' => $name]);

        if ($this->hasRouteByName($name)) {
            throw new RuntimeException(sprintf('Route name "%s" already exists.', $name));
        }

        $this->routesByName[(string) $name] = $route;
    }

    public function hasRouteByName(RouteName $name): bool
    {
        return isset($this->routesByName[(string) $name]);
    }

    public function getRouteByName(RouteName $name): ?Route
    {
        if (! $this->hasRouteByName($name)) {
            return null;
        }

        return $this->routesByName[(string) $name];
    }

    public function hasRouteByPath(UriPath $path): bool
    {
        return $this->getRouteByPath($path) instanceof ResolvedRoute;
    }

    public function getRouteByPath(UriPath $path): ?ResolvedRoute
    {
        $segments = $path->getSegments();
        $lastIndex = array_key_last($segments);
        $tree = &$this->routesBySegment;
        $parameters = [];

        foreach ($segments as $index => $segment) {
            if (! isset($tree[$segment])) {
                foreach (array_keys($tree) as $treeSegment) {
                    if (
                        str_starts_with($treeSegment, '{') &&
                        str_ends_with($treeSegment, '}')
                    ) {
                        $parameters[str_replace(['{', '}'], '', $treeSegment)] = $segment;

                        if ($index === $lastIndex) {
                            $route = $tree[$treeSegment]['route'] ?? null;
                            if (! $route instanceof Route) {
                                return null;
                            }

                            return new ResolvedRoute($route, $parameters);
                        }

                        /** @var array<string, Node> $tree */
                        $tree = &$tree[$treeSegment]['children'];

                        continue 2;
                    }
                }

                return null;
            }

            if ($index === $lastIndex) {
                $route = $tree[$segment]['route'] ?? null;
                if (! $route instanceof Route) {
                    return null;
                }

                return new ResolvedRoute($route, $parameters);
            }

            /** @var array<string, Node> $tree */
            $tree = &$tree[$segment]['children'];
        }

        return null;
    }

    public function append(self $other): void
    {
        foreach ($other->getRoutes() as $route) {
            $this->addRoute($route);
        }
    }

    /**
     * @return array<Route>
     */
    public function getRoutes(): array
    {
        $collectedRoutes = [];

        array_walk_recursive(
            $this->routesBySegment,
            function (mixed $value, string $key) use (&$collectedRoutes): void {
                if ($key === 'route' && $value instanceof Route) {
                    $collectedRoutes[] = $value;
                }
            }
        );

        return $collectedRoutes;
    }
}
