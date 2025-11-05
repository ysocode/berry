<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\Types\RouteName;

final readonly class RouteParser
{
    public function __construct(
        private RouteRegistry $routeRegistry
    ) {}

    public function hasRouteByName(string $name): bool
    {
        return $this->routeRegistry->hasRouteByName(new RouteName($name));
    }

    public function getRouteByName(string $name): ?Route
    {
        return $this->routeRegistry->getRouteByName(new RouteName($name));
    }
}
