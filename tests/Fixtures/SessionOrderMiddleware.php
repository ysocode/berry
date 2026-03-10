<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use RuntimeException;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class SessionOrderMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        $orderAttribute = $request->getAttribute('middleware-order');
        $order = $orderAttribute->value ?? [];

        if (! is_array($order)) {
            throw new RuntimeException('Invalid middleware-order attribute value.');
        }

        $order[] = 'session';

        return $handler->handle($request->withAttribute('middleware-order', $order));
    }
}
