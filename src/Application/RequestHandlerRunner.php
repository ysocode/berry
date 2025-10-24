<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Entities\MiddlewareCollection;
use YSOCode\Berry\Domain\Entities\Route;
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

    public function run(Route $route, ServerRequest $request): Response
    {
        $handler = $route->handler->resolve($this->container);

        $middlewareStack = $this->middlewareStackBuilder->build(
            $handler,
            array_merge($route->middlewareCollection->middlewares, $this->middlewareCollection->middlewares)
        );

        return $middlewareStack->handle($request);
    }
}
