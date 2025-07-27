<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Closure;

final class Dispatcher
{
    /**
     * @var array<Middleware|Closure(Request, Closure): Response>
     */
    private array $middlewares = [];

    public function __construct(
        private readonly Router $router
    ) {}

    /**
     * @param  Middleware|Closure(Request, Closure): Response  $middleware
     */
    public function addMiddleware(Middleware|Closure $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->router->getMatchedRoute($request);
        if ($route instanceof Error) {
            return match (true) {
                $route->equals(new Error('Method not allowed.')) => new Response(Status::METHOD_NOT_ALLOWED, (string) $route),
                $route->equals(new Error('Route not found.')) => new Response(Status::NOT_FOUND, (string) $route),
                default => new Response(Status::INTERNAL_SERVER_ERROR, 'Unknown routing error.')
            };
        }

        $handler = $route->handler;

        $core = function (Request $request) use ($handler): Response {
            $response = match (true) {
                $handler instanceof Handler => $handler->invoke($request),
                default => $handler($request),
            };

            if (! $response instanceof Response) {
                return new Response(Status::INTERNAL_SERVER_ERROR, 'Handler did not return a valid response.');
            }

            return $response;
        };

        $pipeline = $core;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $pipeline = function (Request $request) use ($middleware, $pipeline): Response {
                $response = match (true) {
                    $middleware instanceof Middleware => $middleware->invoke($request, $pipeline),
                    default => $middleware($request, $pipeline),
                };

                if (! $response instanceof Response) {
                    return new Response(Status::INTERNAL_SERVER_ERROR, 'Middleware chain did not return a valid response.');
                }

                return $response;
            };
        }

        return $pipeline($request);
    }
}
