<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Stream;

use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\Resource;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;

final readonly class StreamFactory
{
    public function createFromString(string $content = ''): Stream
    {
        $resource = @fopen('php://temp', 'rw+');
        if ($resource === false) {
            throw new RuntimeException('StreamFactory::createStream() could not open temporary file stream.');
        }

        if (@fwrite($resource, $content) === false) {
            @fclose($resource);
            throw new RuntimeException('Failed to write content to temporary stream.');
        }

        if (@rewind($resource) === false) {
            @fclose($resource);
            throw new RuntimeException('Failed to rewind temporary stream.');
        }

        return $this->createFromResource($resource);
    }

    public function createFromFile(FilePath $filePath, StreamMode $streamMode): Stream
    {
        $filename = (string) $filePath;
        $mode = $streamMode->value;

        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException("Unable to open $filename using mode $mode");
        }

        return $this->createFromResource($resource);
    }

    public function createFromResource(mixed $resource): Stream
    {
        if (! is_resource($resource)) {
            throw new RuntimeException(
                'Parameter 1 of StreamFactory::createFromResource() must be a valid resource.'
            );
        }

        return new Stream(new Resource($resource));
    }
}
