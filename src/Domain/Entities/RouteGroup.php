<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class RouteGroup
{
    use HttpMethodRouteRegistrarTrait;

    public function __construct()
    {
        $this->routeRegistry = new RouteRegistry;
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        foreach ($this->routeRegistry->routeCollections as $routeCollection) {
            foreach ($routeCollection->routes as $route) {
                $route->addMiddleware($middleware);
            }
        }

        return $this;
    }
}
