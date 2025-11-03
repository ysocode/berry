<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class PoweredByMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        $request = $request->withHeader('X-Powered-By', ['Berry']);

        return $handler->handle($request);
    }
}
