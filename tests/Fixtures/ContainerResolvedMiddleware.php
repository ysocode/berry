<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class ContainerResolvedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerResolvedMessage $message
    ) {}

    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle(
            $request->withAttribute('request-logged-at', $this->message->value)
        );
    }
}
