<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;

final class Berry
{
    private readonly Router $router;

    private readonly MiddlewareStackBuilder $middlewareStackBuilder;

    private readonly Dispatcher $dispatcher;

    private readonly ResponseEmitter $responseEmitter;

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function __construct(
        ContainerInterface $container,
        ?Router $router = null,
        ?MiddlewareStackBuilder $middlewareStackBuilder = null,
        ?Dispatcher $dispatcher = null,
        ?ResponseEmitter $responseEmitter = null,
        private(set) array $middlewares = []
    ) {
        $this->router = $router ?? new Router;

        $this->middlewareStackBuilder = $middlewareStackBuilder ?? new MiddlewareStackBuilder($container);

        $this->dispatcher = $dispatcher ?? new Dispatcher($container, $this->router);

        $this->responseEmitter = $responseEmitter ?? new ResponseEmitter;
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(UriPath $path, string|Closure $handler): Route
    {
        return $this->router->get($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(UriPath $path, string|Closure $handler): Route
    {
        return $this->router->put($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(UriPath $path, string|Closure $handler): Route
    {
        return $this->router->post($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(UriPath $path, string|Closure $handler): Route
    {
        return $this->router->delete($path, $handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(UriPath $path, string|Closure $handler): Route
    {
        return $this->router->patch($path, $handler);
    }

    /**
     * @param  Closure(RouteGroup $group): void  $callback
     */
    public function group(Closure $callback): RouteGroup
    {
        return $this->router->group($callback);
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function addMiddlewares(array $middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        return $this;
    }

    public function run(?ServerRequest $request = null): void
    {
        $request ??= new ServerRequestFactory()->fromGlobals();

        $routeMiddlewareStack = $this->dispatcher->dispatch($request);
        if ($routeMiddlewareStack instanceof Error) {
            $response = $this->handleError($routeMiddlewareStack);
        } else {
            $finalMiddlewareStack = $this->middlewareStackBuilder->build($routeMiddlewareStack, $this->middlewares);
            $response = $finalMiddlewareStack->handle($request);
        }

        $this->responseEmitter->emit($response);
    }

    private function handleError(Error $error): Response
    {
        return match (true) {
            $error->equals(new Error('Method not allowed.')) => new Response(HttpStatus::METHOD_NOT_ALLOWED),
            $error->equals(new Error('Route not found.')) => new Response(HttpStatus::NOT_FOUND),
            default => new Response(HttpStatus::INTERNAL_SERVER_ERROR),
        };
    }
}
