<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Middleware;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;

final readonly class Berry
{
    private MiddlewareStackBuilder $middlewareStackBuilder;

    public function __construct(
        private ContainerInterface $container,
        private Router $router = new Router,
        ?MiddlewareStackBuilder $middlewareStackBuilder = null,
        private ResponseEmitter $responseEmitter = new ResponseEmitter,
        public MiddlewareCollection $middlewareCollection = new MiddlewareCollection
    ) {
        $this->middlewareStackBuilder = $middlewareStackBuilder ?? new MiddlewareStackBuilder($this->container);
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function get(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->get(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function put(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->put(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function post(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->post(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function delete(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->delete(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function patch(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->patch(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function head(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->head(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     */
    public function options(string $pathPattern, string|Closure $handler): Route
    {
        return $this->router->options(new RoutePathPattern($pathPattern), new RequestHandler($handler));
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest, RequestHandlerInterface): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        $this->middlewareCollection->addMiddleware(new Middleware($middleware));

        return $this;
    }

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest, RequestHandlerInterface): Response>  $middlewares
     */
    public function addMiddlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->middlewareCollection->addMiddleware(new Middleware($middleware));
        }

        return $this;
    }

    public function run(?ServerRequest $request = null): void
    {
        $request ??= new ServerRequestFactory()->fromGlobals();
        $route = $this->router->getRouteByRequest($request);
        if ($route instanceof Route) {
            $handler = $route->handler->resolve($this->container);
            $middlewareStack = $this->middlewareStackBuilder->build(
                $handler,
                array_merge(
                    $route->middlewareCollection->middlewares,
                    $this->middlewareCollection->middlewares
                )
            );
            $response = $middlewareStack->handle($request);
        } else {
            $response = match (true) {
                $route->equals(new Error('Method not allowed.')) => new Response(HttpStatus::METHOD_NOT_ALLOWED),
                $route->equals(new Error('Route not found.')) => new Response(HttpStatus::NOT_FOUND),
                default => new Response(HttpStatus::INTERNAL_SERVER_ERROR),
            };
        }

        $this->responseEmitter->emit($response);
    }
}
