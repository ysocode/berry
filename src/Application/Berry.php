<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Application\Middlewares\RouteContextMiddleware;
use YSOCode\Berry\Application\Middlewares\RouteParametersMiddleware;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Entities\RouteGroupCollection;
use YSOCode\Berry\Domain\Entities\RouteParser;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Entities\RouteRegistryProxyTrait;
use YSOCode\Berry\Domain\Enums\GroupEvent;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\UriPath;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;

final class Berry
{
    use RouteRegistryProxyTrait;

    private readonly RequestHandlerRunner $requestHandlerRunner;

    private readonly RouteResolver $routeResolver;

    private readonly ErrorRequestHandlerFactory $errorRequestHandlerFactory;

    private ?UriPath $basePath = null;

    public function __construct(
        private readonly ContainerInterface $container,
        ?RouteRegistry $routeRegistry = null,
        ?MiddlewareStackBuilder $middlewareStackBuilder = null,
        ?MiddlewareCollection $middlewareCollection = null,
        private readonly RouteGroupCollection $routeGroupCollection = new RouteGroupCollection,
        ?RequestHandlerRunner $requestHandlerRunner = null,
        ?RouteResolver $routeResolver = null,
        private readonly ResponseEmitter $responseEmitter = new ResponseEmitter,
        ?ErrorRequestHandlerFactory $errorRequestHandlerFactory = null
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
        $this->errorRequestHandlerFactory = $errorRequestHandlerFactory ?? new ErrorRequestHandlerFactory($this->container);
    }

    public function setBasePath(string $basePath): self
    {
        $basePath = new UriPath($basePath);

        $this->basePath = $basePath;
        $this->routeResolver->setBasePath($this->basePath);

        return $this;
    }

    /**
     * @param  Closure(RouteGroup $group): void  $closure
     */
    public function group(Closure $closure): RouteGroup
    {
        $group = new RouteGroup;

        $closure($group);

        $group->on(GroupEvent::AFTER_PROPAGATE, function (RouteGroup $group): void {
            $this->routeRegistry->append($group->routeRegistry);
        });

        $this->routeGroupCollection->addGroup($group);

        return $group;
    }

    public function run(?ServerRequest $request = null): void
    {
        $this->routeGroupCollection->propagateAll();

        $request ??= new ServerRequestFactory()->fromGlobals();
        $resolvedRoute = $this->routeResolver->resolve($request);

        if ($resolvedRoute instanceof ResolvedRoute) {
            $this->addMiddlewares([
                new RouteContextMiddleware($resolvedRoute, new RouteParser($this->routeRegistry, $this->basePath), $this->basePath),
                new RouteParametersMiddleware($resolvedRoute),
            ]);

            $response = $this->requestHandlerRunner->runFromResolvedRoute($resolvedRoute, $request);
        } else {
            $response = $this->requestHandlerRunner->runFromRequestHandler($this->errorRequestHandlerFactory->createFromError($resolvedRoute), $request);
        }

        $this->responseEmitter->emit($response);
    }
}
