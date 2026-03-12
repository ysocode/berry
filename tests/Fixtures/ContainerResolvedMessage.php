<?php

declare(strict_types=1);

namespace Tests\Fixtures;

final readonly class ContainerResolvedMessage
{
    public function __construct(
        public string $value
    ) {}
}
