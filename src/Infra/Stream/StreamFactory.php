<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Stream;

use RuntimeException;
use YSOCode\Berry\Domain\Enums\StreamMode;
use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;

final readonly class StreamFactory
{
    public function createFromString(?string $data = null): Stream
    {
        $resource = fopen('php://temp', 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open temporary stream.');
        }

        $stream = new Stream(new StreamResource($resource));

        if (is_string($data)) {
            $stream->write($data);
        }

        $stream->rewind();

        return $stream;
    }

    public function createFromFile(FilePath $filePath, StreamMode $mode = StreamMode::READ_WRITE_BINARY): Stream
    {
        $resource = fopen((string) $filePath, $mode->value);
        if (! is_resource($resource)) {
            throw new RuntimeException(
                sprintf('Failed to open stream for file "%s" with mode "%s".', $filePath, $mode->value)
            );
        }

        return new Stream(new StreamResource($resource));
    }
}
