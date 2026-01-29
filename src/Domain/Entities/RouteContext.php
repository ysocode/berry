<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\Types\RouteName;
use YSOCode\Berry\Domain\Types\UriPath;

final readonly class RouteContext
{
    const string ATTRIBUTE_ROUTE = 'ysocode.berry.route';

    const string ATTRIBUTE_ROUTE_PARSER = 'ysocode.berry.routeParser';

    const string ATTRIBUTE_BASE_PATH = 'ysocode.berry.basePath';

    public function __construct(
        public Route $route,
        public RouteParser $routeParser,
        public ?UriPath $basePath = null
    ) {}

    /**
     * @param  array<string, string>  $parameters
     */
    public function pathFor(string $name, array $parameters = [], bool $withBasePath = true): ?UriPath
    {
        return $this->routeParser->resolvePathForRouteByName($name, $parameters, $withBasePath);
    }

    public function isCurrentRoute(string $name): bool
    {
        return (bool) $this->route->name?->equals(new RouteName($name));
    }
}
