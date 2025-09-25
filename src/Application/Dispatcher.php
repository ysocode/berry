<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Infra\Http\ClosureHandlerAdapter;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class Dispatcher
{
    private MiddlewareStackBuilder $middlewareStackBuilder;

    public function __construct(
        private ContainerInterface $container,
        private Router $router,
        ?MiddlewareStackBuilder $middlewareStackBuilder = null
    ) {
        $this->middlewareStackBuilder = $middlewareStackBuilder ?? new MiddlewareStackBuilder($this->container);
    }

    public function dispatch(ServerRequest $request): RequestHandlerInterface|Error
    {
        $route = $this->router->getMatchedRoute($request);
        if ($route instanceof Error) {
            return $route;
        }

        $handler = $this->resolveHandler($route->handler);

        return $this->middlewareStackBuilder->build($handler, $route->middlewares);
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
}
