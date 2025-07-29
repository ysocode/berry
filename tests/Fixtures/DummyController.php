<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;

final readonly class DummyController
{
    public function index(Request $request): Response
    {
        return new Response(Status::OK, 'ok');
    }

    public function anotherMethod(Request $request): Response
    {
        return new Response(Status::OK, 'ok');
    }

    private function privateMethod(Request $request): Response
    {
        return new Response(Status::OK, 'ok');
    }

    public function invalidParamCount(): Response
    {
        return new Response(Status::OK, 'ok');
    }

    public function invalidParamType(string $invalidParamType): Response
    {
        return new Response(Status::OK, 'ok');
    }

    public function invalidReturnType(Request $request): string
    {
        return 'invalid return type';
    }
}
