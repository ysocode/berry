<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Entities\RouteRegistryProxyTrait;
use YSOCode\Berry\Domain\Enums\BerryEvent;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Support\EventTrait;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;

final class Berry
{
    /** @use EventTrait<self, BerryEvent> */
    use EventTrait, RouteRegistryProxyTrait;

    private readonly MiddlewareStackBuilder $middlewareStackBuilder;

    public function __construct(
        private readonly ContainerInterface $container,
        ?RouteRegistry $routeRegistry = null,
        ?MiddlewareStackBuilder $middlewareStackBuilder = null,
        private readonly ResponseEmitter $responseEmitter = new ResponseEmitter,
        ?MiddlewareCollection $middlewareCollection = null
    ) {
        $this->routeRegistry = $routeRegistry ?? new RouteRegistry;
        $this->middlewareCollection = $middlewareCollection ?? new MiddlewareCollection;
        $this->middlewareStackBuilder = $middlewareStackBuilder ?? new MiddlewareStackBuilder($this->container);
    }

    /**
     * @param  Closure(RouteGroup $group): void  $closure
     */
    public function group(Closure $closure): RouteGroup
    {
        $group = new RouteGroup;

        $closure($group);

        $this->on(BerryEvent::BEFORE_RUN, function (self $berry) use ($group): void {
            $group->propagate();
            $berry->routeRegistry->append($group->routeRegistry);
        });

        return $group;
    }

    public function run(?ServerRequest $request = null): void
    {
        $this->emit(BerryEvent::BEFORE_RUN);

        $request ??= new ServerRequestFactory()->fromGlobals();
        $route = $this->getRouteByRequest($request);
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

    public function getRouteByRequest(ServerRequest $request): Route|Error
    {
        $path = $request->uri->path ?? new UriPath('/');

        return $this->routeRegistry->getRouteByMethodAndPath($request->method, $path);
    }
}
