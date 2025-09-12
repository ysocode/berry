<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Closure;

final readonly class ClosureHandlerAdapter implements RequestHandlerInterface
{
    /**
     * @param  Closure(ServerRequest $request): Response  $handler
     */
    public function __construct(private Closure $handler) {}

    public function handle(ServerRequest $request): Response
    {
        return ($this->handler)($request);
    }
}
