<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use RuntimeException;
use YSOCode\Berry\Domain\Types\Attribute;
use YSOCode\Berry\Domain\Types\UriPath;
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

        $basePathAttribute = $request->getAttribute(RouteContext::ATTRIBUTE_BASE_PATH);

        if (! $basePathAttribute instanceof Attribute) {
            throw new RuntimeException('Base path attribute not found.');
        }

        if ($basePathAttribute->value !== null && ! $basePathAttribute->value instanceof UriPath) {
            throw new RuntimeException('Base path attribute invalid in request.');
        }

        return new RouteContext($routeAttribute->value, $routeParserAttribute->value, $basePathAttribute->value);
    }
}
