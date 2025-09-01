<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use RuntimeException;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

class Router
{
    /**
     * @var array<string, array<string, Route>>
     */
    private array $routes = [];

    /**
     * @var array<string, true>
     */
    private array $registeredPaths = [];

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function get(Path $path, RequestHandlerInterface|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::GET, $path, $handler);
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function put(Path $path, RequestHandlerInterface|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handler);
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function post(Path $path, RequestHandlerInterface|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::POST, $path, $handler);
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(Path $path, RequestHandlerInterface|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handler);
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(Path $path, RequestHandlerInterface|Closure $handler): Route
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handler);
    }

    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    private function addRoute(HttpMethod $method, Path $path, RequestHandlerInterface|Closure $handler): Route
    {
        if (array_key_exists((string) $path, $this->routes[$method->value] ?? [])) {
            throw new RuntimeException(
                sprintf('Route %s %s already exists.', $method->value, $path)
            );
        }

        $route = new Route($method, $path, $handler);

        $this->routes[$method->value][(string) $path] = $route;

        $this->registeredPaths[(string) $path] = true;

        return $route;
    }

    public function getMatchedRoute(ServerRequest $request): Route|Error
    {
        $path = $request->uri->path ?? new Path('/');

        $route = $this->routes[$request->method->value][(string) $path] ?? null;

        if ($route instanceof Route) {
            return $route;
        }

        $pathExists = $this->registeredPaths[(string) $path] ?? false;
        if ($pathExists) {
            return new Error('Method not allowed.');
        }

        return new Error('Route not found.');
    }
}
