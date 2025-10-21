<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

/**
 * @phpstan-type Node array{children: array<string, mixed>, routes: array<Route>}
 */
final class RouteCollection
{
    /** @var array<string, Node> */
    private array $routeBySegments = [];

    public function addRoute(Route $route): void
    {
        $this->routeBySegments = $this->addRouteBySegments(
            $this->routeBySegments,
            $route->pathPattern->getSegments(),
            $route
        );
    }

    /**
     * @param  array<string, Node>  $tree
     * @param  array<string>  $segments
     * @return array<string, Node>
     */
    private function addRouteBySegments(array $tree, array $segments, Route $route): array
    {
        if ($segments === []) {
            return $tree;
        }

        $segment = array_shift($segments);

        $tree[$segment] ??= [
            'children' => [],
            'routes' => [],
        ];

        if (count($segments) > 0) {
            /** @var array<string, Node> $children */
            $children = $tree[$segment]['children'];

            $tree[$segment]['children'] = $this->addRouteBySegments($children, $segments, $route);

            return $tree;
        }

        $tree[$segment]['routes'][] = $route;

        return $tree;
    }
}
