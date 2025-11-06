<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

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
}
