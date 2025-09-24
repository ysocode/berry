<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class DummyHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        $bodyContent = 'Hello, World!';
        $requestIdHeader = $request->getHeader(new HeaderName('X-Request-ID'));

        if ($requestIdHeader instanceof Header) {
            [$requestId] = $requestIdHeader->values;
            $bodyContent = json_encode(['requestId' => $requestId]);
            if (! is_string($bodyContent)) {
                return new Response(HttpStatus::BAD_REQUEST);
            }
        }

        return new Response(HttpStatus::OK)
            ->withBody(
                new StreamFactory()->createFromString($bodyContent)
            );
    }
}
