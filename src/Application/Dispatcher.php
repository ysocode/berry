<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Infra\Http\ClosureHandlerAdapter;
use YSOCode\Berry\Infra\Http\ClosureMiddlewareAdapter;
use YSOCode\Berry\Infra\Http\MiddlewareHandlerAdapter;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final readonly class Dispatcher
{
    public function __construct(
        private ContainerInterface $container,
        private Router $router
    ) {}

    public function dispatch(ServerRequest $request): Response
    {
        $routeOrError = $this->router->getMatchedRoute($request);

        if ($routeOrError instanceof Error) {
            $body = new StreamFactory()->createFromString((string) $routeOrError);

            return match (true) {
                $routeOrError->equals(new Error('Method not allowed.')) => new Response(HttpStatus::METHOD_NOT_ALLOWED, body: $body),
                $routeOrError->equals(new Error('Route not found.')) => new Response(HttpStatus::NOT_FOUND, body: $body),
                default => new Response(HttpStatus::INTERNAL_SERVER_ERROR, body: $body),
            };
        }

        $handler = $this->resolveHandler($routeOrError->handler);
        $pipeline = $this->buildPipeline($handler, $routeOrError->middlewares);

        return $pipeline->handle($request);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    private function resolveHandler(string|Closure $handler): RequestHandlerInterface
    {
        if ($handler instanceof Closure) {
            return new ClosureHandlerAdapter($handler);
        }

        $resolved = $this->container->get($handler);
        if (! $resolved instanceof RequestHandlerInterface) {
            throw new RuntimeException(sprintf(
                'Handler must implement RequestHandlerInterface, got %s',
                get_debug_type($resolved)
            ));
        }

        return $resolved;
    }

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    private function buildPipeline(RequestHandlerInterface $handler, array $middlewares): RequestHandlerInterface
    {
        $pipeline = $handler;

        foreach (array_reverse($middlewares) as $middleware) {
            $resolved = $this->resolveMiddleware($middleware);

            $pipeline = new MiddlewareHandlerAdapter($resolved, $pipeline);
        }

        return $pipeline;
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    private function resolveMiddleware(string|Closure $middleware): MiddlewareInterface
    {
        if ($middleware instanceof Closure) {
            return new ClosureMiddlewareAdapter($middleware);
        }

        $resolved = $this->container->get($middleware);
        if (! $resolved instanceof MiddlewareInterface) {
            throw new RuntimeException(sprintf(
                'Middleware must implement MiddlewareInterface, got %s',
                get_debug_type($resolved)
            ));
        }

        return $resolved;
    }
}
