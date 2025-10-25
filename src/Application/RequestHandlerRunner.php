<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\Attribute;
use YSOCode\Berry\Domain\Types\AttributeName;
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
        foreach ($resolvedRoute->parameters as $parameter => $value) {
            $request = $request->withAttribute(new Attribute(new AttributeName($parameter), $value));
        }

        $handler = $resolvedRoute->route->handler->resolve($this->container);

        $middlewareStack = $this->middlewareStackBuilder->build(
            $handler,
            array_merge($resolvedRoute->route->middlewareCollection->middlewares, $this->middlewareCollection->middlewares)
        );

        return $middlewareStack->handle($request);
    }
}
