<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Middleware;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;
use YSOCode\Berry\Infra\StreamFactory;

final class Dispatcher
{
    /**
     * @var array<Middleware|Closure(Request, Closure(Request): Response): Response>
     */
    private array $middlewares = [];

    public function __construct(
        private readonly Router $router
    ) {}

    /**
     * @param  Middleware|Closure(Request, Closure(Request): Response): Response  $middleware
     */
    public function addMiddleware(Middleware|Closure $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function dispatch(Request $request, ContainerInterface $container): Response
    {
        $route = $this->router->getMatchedRoute($request);
        if ($route instanceof Error) {
            return match (true) {
                $route->equals(new Error('Method not allowed.')) => new Response(Status::METHOD_NOT_ALLOWED, [], new StreamFactory()->createFromString((string) $route)),
                $route->equals(new Error('Route not found.')) => new Response(Status::NOT_FOUND, [], new StreamFactory()->createFromString((string) $route)),
                default => new Response(Status::INTERNAL_SERVER_ERROR, [], new StreamFactory()->createFromString('Unknown routing error.'))
            };
        }

        $handler = $route->handler;

        $core = function (Request $request) use ($handler, $container): Response {
            $response = match (true) {
                $handler instanceof Handler => $handler->invoke($request, $container),
                default => $handler($request),
            };

            if (! $response instanceof Response) {
                return new Response(Status::INTERNAL_SERVER_ERROR, [], new StreamFactory()->createFromString('Handler did not return a valid response.'));
            }

            return $response;
        };

        $pipeline = $core;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $pipeline = function (Request $request) use ($middleware, $pipeline, $container): Response {
                $response = match (true) {
                    $middleware instanceof Middleware => $middleware->invoke($request, $pipeline, $container),
                    default => $middleware($request, $pipeline),
                };

                if (! $response instanceof Response) {
                    return new Response(Status::INTERNAL_SERVER_ERROR, [], new StreamFactory()->createFromString('Middleware chain did not return a valid response.'));
                }

                return $response;
            };
        }

        return $pipeline($request);
    }
}
