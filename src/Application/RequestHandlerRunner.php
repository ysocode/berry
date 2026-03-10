<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class RequestHandlerRunner
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly MiddlewareStackBuilder $middlewareStackBuilder,
        private readonly MiddlewareCollection $middlewareCollection
    ) {}

    public function runFromRequestHandler(RequestHandler $handler, ServerRequest $request): Response
    {
        $middlewareStack = $this->middlewareStackBuilder->build(
            $handler->resolve($this->container),
            $this->middlewareCollection->middlewares
        );

        return $middlewareStack->handle($request);
    }

    public function runFromResolvedRoute(ResolvedRoute $resolvedRoute, ServerRequest $request): Response
    {
        $resolvedHandler = $resolvedRoute->route->handler->resolve($this->container);

        $middlewareStack = $this->middlewareStackBuilder->build(
            $resolvedHandler,
            array_merge($this->middlewareCollection->middlewares, $resolvedRoute->route->middlewareCollection->middlewares)
        );

        return $middlewareStack->handle($request);
    }
}
