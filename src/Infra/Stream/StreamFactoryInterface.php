<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Stream;

use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;

interface StreamFactoryInterface
{
    public function createFromString(string $content = ''): Stream;

    public function createFromFile(FilePath $filePath, StreamMode $streamMode): Stream;

    public function createFromResource(mixed $resource): Stream;
}
