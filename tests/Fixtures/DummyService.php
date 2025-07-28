<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final readonly class DummyService
{
    public function getMessage(): string
    {
        return 'Hello from service';
    }
}
