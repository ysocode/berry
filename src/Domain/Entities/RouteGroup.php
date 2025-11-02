<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Types\RoutePathPattern;

final class RouteGroup
{
    use RouteRegistryProxyTrait;

    private bool $propagated = false;

    public function __construct(
        ?RouteRegistry $routeRegistry = null,
        ?MiddlewareCollection $middlewareCollection = null,
        private ?RoutePathPattern $prefix = null
    ) {
        $this->routeRegistry = $routeRegistry ?? new RouteRegistry;
        $this->middlewareCollection = $middlewareCollection ?? new MiddlewareCollection;
    }

    public function addPrefix(string $pathPattern): self
    {
        $this->prefix = new RoutePathPattern($pathPattern);

        return $this;
    }

    private function propagatePrefix(): void
    {
        foreach ($this->routeRegistry->getRoutes() as $route) {
            if ($this->prefix instanceof RoutePathPattern) {
                $route->addPrefix((string) $this->prefix);
            }
        }
    }

    private function propagateMiddlewares(): void
    {
        foreach ($this->routeRegistry->getRoutes() as $route) {
            $route->middlewareCollection->append($this->middlewareCollection);
        }
    }

    private function propagate(): void
    {
        $this->propagatePrefix();
        $this->propagateMiddlewares();

        $this->propagated = true;
    }

    public function shareRoutesWith(Berry $berry): void
    {
        if (! $this->propagated) {
            $this->propagate();
        }

        $berry->routeRegistry->append($this->routeRegistry);
    }
}
