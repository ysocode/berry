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
        $route = $this->router->match($request);

        if (! $route instanceof Route) {
            return new Response(Status::NOT_FOUND, 'Route not found.');
        }

        $response = ($route->handler)($request);

        if (! $response instanceof Response) {
            return new Response(Status::INTERNAL_SERVER_ERROR, 'Handler did not return a valid response.');
        }

        return $response;
    }
}
