<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

interface MiddlewareInterface
{
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response;
}
