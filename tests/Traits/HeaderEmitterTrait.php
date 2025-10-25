<?php

declare(strict_types=1);

namespace Tests\Traits;

trait HeaderEmitterTrait
{
    /**
     * @var array<array{header: string, replace: bool, statusCode: int}>
     */
    private array $emittedHeaders = [];

    private function headerEmitter(string $header, bool $replace = true, int $statusCode = 0): void
    {
        $this->emittedHeaders[] = [
            'header' => $header,
            'replace' => $replace,
            'statusCode' => $statusCode,
        ];
    }
}
