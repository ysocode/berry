<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use YSOCode\Berry\Domain\Entities\HttpMethodRouteRegistrarTrait;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class Router
{
    use HttpMethodRouteRegistrarTrait;

    public function __construct()
    {
        $this->routeRegistry = new RouteRegistry;
    }

    public function getMatchedRoute(ServerRequest $request): Route|Error
    {
        $path = $request->uri->path ?? new Path('/');

        $route = $this->routeRegistry->getRouteByMethodAndPath(
            $request->method,
            $path,
        );

        if ($route instanceof Route) {
            return $route;
        }

        if ($this->routeRegistry->hasRouteByPath($path)) {
            return new Error('Method not allowed.');
        }

        return new Error('Route not found.');
    }

    /**
     * @param  Closure(RouteGroup $group): void  $callback
     */
    public function group(Closure $callback): RouteGroup
    {
        $group = new RouteGroup;

        $callback($group);

        $this->routeRegistry->addGroup($group);

        return $group;
    }
}
