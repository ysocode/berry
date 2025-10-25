<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Psr\Container\ContainerInterface;
use YSOCode\Berry\Domain\Types\Middleware;

final readonly class MiddlewareStackBuilder
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    /**
     * @param  array<Middleware>  $middlewares
     */
    public function build(RequestHandlerInterface $handler, array $middlewares): RequestHandlerInterface
    {
        $pipeline = $handler;

        foreach (array_reverse($middlewares) as $middleware) {
            $pipeline = new MiddlewareHandlerAdapter($middleware->resolve($this->container), $pipeline);
        }

        return $pipeline;
    }
}
