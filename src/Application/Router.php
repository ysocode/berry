<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use LogicException;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;

final class Router
{
    /**
     * @var array<string, array<string, Route>>
     */
    private array $routes = [];

    /**
     * @var array<string, Route>
     */
    private array $namedRoutes = [];

    /**
     * @var array<string, true>
     */
    private array $registeredPaths = [];

    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function get(Path $path, Handler|Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::GET, $path, $handler, $name);
    }

    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function put(Path $path, Handler|Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::PUT, $path, $handler, $name);
    }

    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function post(Path $path, Handler|Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::POST, $path, $handler, $name);
    }

    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function delete(Path $path, Handler|Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::DELETE, $path, $handler, $name);
    }

    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function patch(Path $path, Handler|Closure $handler, ?Name $name = null): void
    {
        $this->addRoute(Method::PATCH, $path, $handler, $name);
    }

    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    private function addRoute(Method $method, Path $path, Handler|Closure $handler, ?Name $name = null): void
    {
        $pathKey = (string) $path;

        if (isset($this->routes[$method->value][$pathKey])) {
            throw new LogicException(sprintf(
                'Route %s %s already exists.',
                $method->value,
                $path
            ));
        }

        $route = new Route($method, $path, $handler, $name);

        $this->routes[$method->value][$pathKey] = $route;

        if ($name instanceof Name) {
            $nameKey = (string) $name;

            if (isset($this->namedRoutes[$nameKey])) {
                throw new LogicException(sprintf(
                    'Route name "%s" already exists.',
                    $name
                ));
            }

            $this->namedRoutes[$nameKey] = $route;
        }

        $this->registeredPaths[$pathKey] = true;
    }

    public function getMatchedRoute(Request $request): Route|Error
    {
        $method = $request->method;
        $path = $request->path;

        $pathKey = (string) $path;

        $route = $this->routes[$method->value][$pathKey] ?? null;

        if ($route instanceof Route) {
            return $route;
        }

        if ($this->registeredPaths[$pathKey] ?? false) {
            return new Error('Method not allowed.');
        }

        return new Error('Route not found.');
    }

    public function getRouteByName(Name $name): ?Route
    {
        return $this->namedRoutes[(string) $name] ?? null;
    }
}
