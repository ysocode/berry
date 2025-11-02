<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Types\RoutePathPattern;

final class RouteGroup
{
    use RouteRegistryProxyTrait;

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
        if ($this->prefix instanceof RoutePathPattern) {
            foreach ($this->routeRegistry->getRoutes() as $route) {
                $route->addPrefix((string) $this->prefix);
            }

            $this->prefix = null;
        }
    }

    private function propagateMiddlewares(): void
    {
        if ($this->middlewareCollection->isNotEmpty()) {
            foreach ($this->routeRegistry->getRoutes() as $route) {
                $route->middlewareCollection->append($this->middlewareCollection);
            }

            $this->middlewareCollection->clear();
        }
    }

    private function propagate(): void
    {
        $this->propagatePrefix();
        $this->propagateMiddlewares();
    }

    public function shareRoutesWith(Berry $berry): void
    {
        $this->propagate();

        $berry->routeRegistry->append($this->routeRegistry);
    }
}
