<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;

final readonly class DummyWithDependencyController
{
    public function __construct(private DummyService $service) {}

    public function index(Request $request): Response
    {
        return new Response(Status::OK, $this->service->getMessage());
    }
}
