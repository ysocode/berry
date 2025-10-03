<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class HelloWorldHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        return new Response(
            HttpStatus::OK,
            body: new StreamFactory()->createFromString('Hello, world!')
        );
    }
}
