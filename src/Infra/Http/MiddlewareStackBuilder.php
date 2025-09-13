<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;

final readonly class MiddlewareStackBuilder
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function build(RequestHandlerInterface $handler, array $middlewares): RequestHandlerInterface
    {
        $pipeline = $handler;

        foreach (array_reverse($middlewares) as $middleware) {
            $resolved = $this->resolveMiddleware($middleware);

            $pipeline = new MiddlewareHandlerAdapter($resolved, $pipeline);
        }

        return $pipeline;
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    private function resolveMiddleware(string|Closure $middleware): MiddlewareInterface
    {
        if ($middleware instanceof Closure) {
            return new ClosureMiddlewareAdapter($middleware);
        }

        $resolved = $this->container->get($middleware);
        if (! $resolved instanceof MiddlewareInterface) {
            throw new RuntimeException(sprintf(
                'Middleware must implement MiddlewareInterface, got %s',
                get_debug_type($resolved)
            ));
        }

        return $resolved;
    }
}
