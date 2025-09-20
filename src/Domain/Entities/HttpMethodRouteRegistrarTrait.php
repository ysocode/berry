<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

trait HttpMethodRouteRegistrarTrait
{
    public readonly RouteRegistry $routeRegistry;

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::GET, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::PUT, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::POST, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::DELETE, $path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(Path $path, string|Closure $handler): Route
    {
        return $this->routeRegistry->addRoute(HttpMethod::PATCH, $path, $handler);
    }
}
