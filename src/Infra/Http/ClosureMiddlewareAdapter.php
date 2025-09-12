<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Closure;

final readonly class ClosureMiddlewareAdapter implements MiddlewareInterface
{
    /**
     * @param  Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function __construct(private Closure $middleware) {}

    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        return ($this->middleware)($request, $handler);
    }
}
