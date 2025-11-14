<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\Types\PathParameter;
use YSOCode\Berry\Domain\Types\PathParameterName;
use YSOCode\Berry\Domain\Types\RouteName;
use YSOCode\Berry\Domain\Types\UriPath;

final readonly class RouteParser
{
    public function __construct(
        private RouteRegistry $routeRegistry,
        private ?UriPath $basePath = null,
    ) {}

    public function hasRouteByName(string $name): bool
    {
        return $this->routeRegistry->hasRouteByName(new RouteName($name));
    }

    public function getRouteByName(string $name): ?Route
    {
        return $this->routeRegistry->getRouteByName(new RouteName($name));
    }

    /**
     * @param  array<string, string>  $parameters
     */
    public function resolvePathForRouteByName(string $name, array $parameters = [], bool $withBasePath = true): ?UriPath
    {
        $route = $this->routeRegistry->getRouteByName(new RouteName($name));
        if (! $route instanceof Route) {
            return null;
        }

        $formattedParameters = [];
        foreach ($parameters as $name => $value) {
            $formattedParameters[] = new PathParameter(new PathParameterName($name), $value);
        }

        $path = $route->pathPattern->buildPath($formattedParameters);

        if (! $this->basePath instanceof UriPath) {
            return $path;
        }

        if (! $withBasePath) {
            return $path;
        }

        return $path->prepend($this->basePath);
    }
}
