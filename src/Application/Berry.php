<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Entities\RouteRegistryProxyTrait;
use YSOCode\Berry\Domain\Enums\BerryEvent;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Traits\EventTrait;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;

final class Berry
{
    /** @use EventTrait<self, BerryEvent> */
    use EventTrait, RouteRegistryProxyTrait;

    private readonly RequestHandlerRunner $requestHandlerRunner;

    private readonly RouteResolver $routeResolver;

    public function __construct(
        private readonly ContainerInterface $container,
        ?RouteRegistry $routeRegistry = null,
        ?MiddlewareStackBuilder $middlewareStackBuilder = null,
        ?MiddlewareCollection $middlewareCollection = null,
        ?RequestHandlerRunner $requestHandlerRunner = null,
        ?RouteResolver $routeResolver = null,
        private readonly ResponseFactory $responseFactory = new ResponseFactory,
        private readonly ResponseEmitter $responseEmitter = new ResponseEmitter
    ) {
        $this->routeRegistry = $routeRegistry ?? new RouteRegistry;

        $middlewareStackBuilder ??= new MiddlewareStackBuilder($this->container);

        $this->middlewareCollection = $middlewareCollection ?? new MiddlewareCollection;
        $this->requestHandlerRunner = $requestHandlerRunner ?? new RequestHandlerRunner(
            $this->container,
            $middlewareStackBuilder,
            $this->middlewareCollection
        );

        $this->routeResolver = $routeResolver ?? new RouteResolver($this->routeRegistry);
    }

    /**
     * @param  Closure(RouteGroup $group): void  $closure
     */
    public function group(Closure $closure): RouteGroup
    {
        $group = new RouteGroup;

        $closure($group);

        $this->on(BerryEvent::BEFORE_RUN, $group->shareRoutesWith(...));

        return $group;
    }

    public function run(?ServerRequest $request = null): void
    {
        $this->emit(BerryEvent::BEFORE_RUN);

        $request ??= new ServerRequestFactory()->fromGlobals();
        $resolvedRoute = $this->routeResolver->resolve($request);

        $response = $resolvedRoute instanceof ResolvedRoute
        ? $this->requestHandlerRunner->run($resolvedRoute, $request)
        : $this->responseFactory->fromError($resolvedRoute);

        $this->responseEmitter->emit($response);
    }
}
