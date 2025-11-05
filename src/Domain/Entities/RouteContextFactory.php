<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use RuntimeException;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteContextFactory
{
    public function createFromRequest(ServerRequest $request): RouteContext
    {
        $routeAttribute = $request->getAttribute(RouteContext::ATTRIBUTE_ROUTE);
        if (! $routeAttribute?->value instanceof Route) {
            throw new RuntimeException('Route attribute not found or invalid in request.');
        }

        $routeParserAttribute = $request->getAttribute(RouteContext::ATTRIBUTE_ROUTE_PARSER);
        if (! $routeParserAttribute?->value instanceof RouteParser) {
            throw new RuntimeException('Route parser attribute not found or invalid in request.');
        }

        return new RouteContext($routeAttribute->value, $routeParserAttribute->value);
    }
}
