<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\AttributeName;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class InspectRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        $loggedAtAttribute = $request->getAttribute(new AttributeName('request-logged-at'));
        $loggedAt = $loggedAtAttribute->value ?? 'No log available';
        if (! is_string($loggedAt)) {
            return new Response(
                HttpStatus::INTERNAL_SERVER_ERROR,
                body: new StreamFactory()->createFromString('Invalid request-logged-at attribute value.')
            );
        }

        $poweredByHeader = $request->getHeader(new HeaderName('X-Powered-By'));
        [$poweredBy] = $poweredByHeader->values ?? ['Not powered'];
        if (! is_string($poweredBy)) {
            return new Response(
                HttpStatus::INTERNAL_SERVER_ERROR,
                body: new StreamFactory()->createFromString('Invalid X-Powered-By header value.')
            );
        }

        return new Response(
            HttpStatus::OK,
            body: new StreamFactory()->createFromString("Log: {$loggedAt}. Powered by: {$poweredBy}.")
        );
    }
}
