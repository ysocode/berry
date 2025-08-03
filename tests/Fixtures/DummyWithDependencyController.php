<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Http\Request;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final readonly class DummyWithDependencyController
{
    public function __construct(private DummyService $service) {}

    public function index(Request $request): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString($this->service->getMessage()));
    }
}
