<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application\Middlewares;

use YSOCode\Berry\Domain\Entities\RouteContext;
use YSOCode\Berry\Domain\Entities\RouteParser;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        public ResolvedRoute $resolvedRoute,
        public RouteParser $routeParser
    ) {}

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle(
            $request->withAttribute(RouteContext::ATTRIBUTE_ROUTE, $this->resolvedRoute->route)
                ->withAttribute(RouteContext::ATTRIBUTE_ROUTE_PARSER, $this->routeParser)
        );
    }
}
