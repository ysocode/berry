<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;

final class RouteGroup
{
    use RouteRegistryProxyTrait;

    private ?RoutePathPattern $prefix = null;

    public function __construct(
        ?RouteRegistry $routeRegistry = null,
        ?MiddlewareCollection $middlewareCollection = null
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
                $route->addPrefix($this->prefix);
            }
        }
    }

    private function propagateMiddlewares(): void
    {
        foreach ($this->routeRegistry->getRoutes() as $route) {
            $route->middlewareCollection->append($this->middlewareCollection);
        }
    }

    public function propagate(): void
    {
        $this->propagatePrefix();
        $this->propagateMiddlewares();
    }
}
