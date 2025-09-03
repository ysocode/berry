<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class Router
{
    private RouteRegistry $routeRegistry;

    public function __construct()
    {
        $this->routeRegistry = new RouteRegistry;
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function get(Path $path, RequestHandlerInterface|Closure $handler): self
    {
        $this->routeRegistry->addRoute(HttpMethod::GET, $path, $handler);

        return $this;
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function put(Path $path, RequestHandlerInterface|Closure $handler): self
    {
        $this->routeRegistry->addRoute(HttpMethod::PUT, $path, $handler);

        return $this;
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function post(Path $path, RequestHandlerInterface|Closure $handler): self
    {
        $this->routeRegistry->addRoute(HttpMethod::POST, $path, $handler);

        return $this;
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(Path $path, RequestHandlerInterface|Closure $handler): self
    {
        $this->routeRegistry->addRoute(HttpMethod::DELETE, $path, $handler);

        return $this;
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(Path $path, RequestHandlerInterface|Closure $handler): self
    {
        $this->routeRegistry->addRoute(HttpMethod::PATCH, $path, $handler);

        return $this;
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

    public function withName(Name $name): self
    {
        $this->routeRegistry->withName($name);

        return $this;
    }
}
