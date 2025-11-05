<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

final readonly class RouteContext
{
    const string ATTRIBUTE_ROUTE = 'ysocode.berry.route';

    const string ATTRIBUTE_ROUTE_PARSER = 'ysocode.berry.route-parser';

    public function __construct(
        public Route $route,
        public RouteParser $routeParser
    ) {}
}
