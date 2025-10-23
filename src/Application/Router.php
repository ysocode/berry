<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class Router
{
    public function __construct(
        private RouteRegistry $routeRegistry = new RouteRegistry
    ) {}

    public function get(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::GET, $pathPattern, $handler);
    }

    public function put(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::PUT, $pathPattern, $handler);
    }

    public function post(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::POST, $pathPattern, $handler);
    }

    public function delete(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::DELETE, $pathPattern, $handler);
    }

    public function patch(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::PATCH, $pathPattern, $handler);
    }

    public function head(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::HEAD, $pathPattern, $handler);
    }

    public function options(RoutePathPattern $pathPattern, RequestHandler $handler): Route
    {
        return $this->routeRegistry->map(HttpMethod::OPTIONS, $pathPattern, $handler);
    }

    public function getRouteByRequest(ServerRequest $request): Route|Error
    {
        $path = $request->uri->path ?? new UriPath('/');

        return $this->routeRegistry->getRouteByMethodAndPath($request->method, $path);
    }
}
