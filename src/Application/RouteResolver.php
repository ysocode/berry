<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Domain\Types\UriPath;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteResolver
{
    private ?UriPath $basePath = null;

    public function __construct(
        private readonly RouteRegistry $routeRegistry
    ) {}

    public function setBasePath(UriPath $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function resolve(ServerRequest $request): ResolvedRoute|Error
    {
        $path = $this->getFormattedPath($request);

        $route = $this->routeRegistry->getMatchedRoute($request->method, $path);
        if ($route instanceof Error) {
            return $route;
        }

        return new ResolvedRoute(
            $route,
            $route->pathPattern->extractParameters($path)
        );
    }

    private function getFormattedPath(ServerRequest $request): UriPath
    {
        $path = $request->uri->path;
        if (! $path instanceof UriPath) {
            return new UriPath('/');
        }

        if (! $this->basePath instanceof UriPath) {
            return $path;
        }

        return $path->strip($this->basePath);
    }
}
