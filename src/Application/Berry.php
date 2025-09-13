<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class Berry
{
    private readonly Router $router;

    private readonly MiddlewareStackBuilder $middlewareStackBuilder;

    private readonly Dispatcher $dispatcher;

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function __construct(
        ContainerInterface $container,
        private(set) array $middlewares = []
    ) {
        $this->router = new Router;

        $this->middlewareStackBuilder = new MiddlewareStackBuilder($container);

        $this->dispatcher = new Dispatcher($container, $this->router);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(Path $path, string|Closure $handler): Route
    {
        return $this->router->get($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(Path $path, string|Closure $handler): Route
    {
        return $this->router->put($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(Path $path, string|Closure $handler): Route
    {
        return $this->router->post($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(Path $path, string|Closure $handler): Route
    {
        return $this->router->delete($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(Path $path, string|Closure $handler): Route
    {
        return $this->router->patch($path, $handler);
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function run(): void
    {
        $request = new ServerRequestFactory()->fromGlobals();

        $middlewareStack = $this->dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            $response = $this->handleError($middlewareStack);
        } else {
            $finalMiddlewareStack = $this->middlewareStackBuilder->build($middlewareStack, $this->middlewares);
            $response = $finalMiddlewareStack->handle($request);
        }

        new ResponseEmitter()->emit($response);
    }

    private function handleError(Error $error): Response
    {
        $body = new StreamFactory()->createFromString((string) $error);

        return match (true) {
            $error->equals(new Error('Method not allowed.')) => new Response(HttpStatus::METHOD_NOT_ALLOWED, body: $body),
            $error->equals(new Error('Route not found.')) => new Response(HttpStatus::NOT_FOUND, body: $body),
            default => new Response(HttpStatus::INTERNAL_SERVER_ERROR, body: $body),
        };
    }
}
