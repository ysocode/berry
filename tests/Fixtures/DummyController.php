<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Http\Request;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final readonly class DummyController
{
    public function index(Request $request): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString('ok'));
    }

    public function anotherMethod(Request $request): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString('ok'));
    }

    private function privateMethod(Request $request): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString('ok'));
    }

    public function invalidParamCount(): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString('ok'));
    }

    public function invalidParamType(string $invalidParamType): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString('ok'));
    }

    public function invalidReturnType(Request $request): string
    {
        return 'invalid return type';
    }
}
