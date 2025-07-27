<?php

declare(strict_types=1);

namespace YSOCode\Berry;

final readonly class Dispatcher
{
    public function __construct(
        private Router $router
    ) {}

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

        $response = match (true) {
            $handler instanceof Handler => $handler->invoke($request),
            default => $handler($request),
        };

        if (! $response instanceof Response) {
            return new Response(Status::INTERNAL_SERVER_ERROR, 'Handler did not return a valid response.');
        }

        return $response;
    }
}
