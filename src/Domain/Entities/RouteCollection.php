<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\UriPath;

/**
 * @phpstan-type Node array{children: array<string, mixed>, route: ?Route}
 */
final class RouteCollection
{
    /**
     * @var array<string, Node>
     */
    private array $routeBySegments = [];

    public function addRoute(Route $route): void
    {
        $segments = $route->pathPattern->getSegments();
        $lastIndex = array_key_last($segments);

        $tree = &$this->routeBySegments;

        foreach ($segments as $index => $segment) {
            $tree[$segment] ??= ['children' => [], 'route' => null];

            if ($index === $lastIndex) {
                if (isset($tree[$segment]['route'])) {
                    throw new RuntimeException("Route conflict: {$route->pathPattern}");
                }

                $tree[$segment]['route'] = $route;
            }

            /** @var array<string, Node> $tree */
            $tree = &$tree[$segment]['children'];
        }
    }

    public function hasRouteByPath(UriPath $path): bool
    {
        return $this->getRouteByPath($path) instanceof Route;
    }

    public function getRouteByPath(UriPath $path): ?Route
    {
        $segments = $path->getSegments();
        $lastIndex = array_key_last($segments);

        $tree = &$this->routeBySegments;

        foreach ($segments as $index => $segment) {
            if (! isset($tree[$segment])) {
                foreach (array_keys($tree) as $treeSegment) {
                    if (
                        str_starts_with($treeSegment, '{') &&
                        str_ends_with($treeSegment, '}')
                    ) {
                        if ($index === $lastIndex) {
                            return $tree[$treeSegment]['route'] ?? null;
                        }

                        /** @var array<string, Node> $tree */
                        $tree = &$tree[$treeSegment]['children'];

                        continue 2;
                    }
                }

                return null;
            }

            if ($index === $lastIndex) {
                return $tree[$segment]['route'] ?? null;
            }

            /** @var array<string, Node> $tree */
            $tree = &$tree[$segment]['children'];
        }

        return null;
    }
}
