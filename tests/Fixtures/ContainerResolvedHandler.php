<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class ContainerResolvedHandler implements RequestHandlerInterface
{
    public function __construct(
        private ContainerResolvedMessage $message
    ) {}

    public function handle(ServerRequest $request): Response
    {
        return new ResponseFactory()->createFromString($this->message->value);
    }
}
