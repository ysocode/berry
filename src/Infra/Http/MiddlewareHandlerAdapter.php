<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

final readonly class MiddlewareHandlerAdapter implements RequestHandlerInterface
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $handler
    ) {}

    public function handle(ServerRequest $request): Response
    {
        return $this->middleware->process($request, $this->handler);
    }
}
