<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class InspectRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        $loggedAtAttribute = $request->getAttribute('request-logged-at');
        $loggedAt = $loggedAtAttribute->value ?? 'No log available';
        if (! is_string($loggedAt)) {
            return new ResponseFactory()
                ->fromBody('Invalid request-logged-at attribute value.')
                ->withStatus(HttpStatus::INTERNAL_SERVER_ERROR);
        }

        $poweredByHeader = $request->getHeader('X-Powered-By');
        [$poweredBy] = $poweredByHeader->values ?? ['Not powered'];
        if (! is_string($poweredBy)) {
            return new ResponseFactory()
                ->fromBody('Invalid X-Powered-By header value.')
                ->withStatus(HttpStatus::INTERNAL_SERVER_ERROR);
        }

        return new ResponseFactory()->fromBody("Log: {$loggedAt}. Powered by: {$poweredBy}.");
    }
}
