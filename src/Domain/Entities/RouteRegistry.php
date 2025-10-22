<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteRegistry
{
    /** @var array<string, RouteCollection> */
    private array $routeCollectionByMethods = [];

    public function __construct()
    {
        foreach (HttpMethod::getValues() as $method) {
            $this->routeCollectionByMethods[$method] = new RouteCollection;
        }
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function map(HttpMethod $method, RoutePathPattern $pathPattern, string|Closure $handler): Route
    {
        $route = new Route($method, $pathPattern, $handler);

        $this->routeCollectionByMethods[$method->value]->addRoute($route);

        return $route;
    }

    public function getRouteByMethodAndPath(HttpMethod $method, UriPath $path): ?Route
    {
        return $this->routeCollectionByMethods[$method->value]->getRouteByPath($path);
    }
}
