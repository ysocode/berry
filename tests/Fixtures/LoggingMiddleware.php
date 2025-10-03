<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DateTimeImmutable;
use YSOCode\Berry\Domain\ValueObjects\Attribute;
use YSOCode\Berry\Domain\ValueObjects\AttributeName;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class LoggingMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        $fixedDate = new DateTimeImmutable('1997-08-22 00:00:00');

        $request = $request->withAttribute(
            new Attribute(
                new AttributeName('request-logged-at'),
                $fixedDate->format('Y-m-d H:i:s')
            )
        );

        return $handler->handle($request);
    }
}
