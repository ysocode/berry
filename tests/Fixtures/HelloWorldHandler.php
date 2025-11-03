<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class HelloWorldHandler implements RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        return new ResponseFactory()->fromBody('Hello, world!');
    }
}
