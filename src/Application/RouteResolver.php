<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Domain\Types\UriPath;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class RouteResolver
{
    public function __construct(
        private RouteRegistry $routeRegistry
    ) {}

    public function resolve(ServerRequest $request): ResolvedRoute|Error
    {
        $path = $request->uri->path ?? new UriPath('/');

        return $this->routeRegistry->getRouteByMethodAndPath($request->method, $path);
    }
}
