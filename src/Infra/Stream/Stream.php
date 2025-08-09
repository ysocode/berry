<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Stream;

use InvalidArgumentException;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;

final class Stream
{
    public private(set) ?StreamResource $resource;

    public private(set) bool $isReadable;

    public private(set) bool $isWritable;

    public private(set) bool $isSeekable;

    public private(set) ?int $size;

    public function __construct(
        StreamResource $resource
    ) {
        $this->resource = $resource;

        $this->extractParts();
    }

    private function extractParts(): void
    {
        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        $meta = stream_get_meta_data($this->resource->value);

        $streamMode = StreamMode::from($meta['mode']);

        $this->isReadable = $streamMode->isReadable();
        $this->isWritable = $streamMode->isWritable();

        $this->isSeekable = $meta['seekable'];

        $stat = fstat($this->resource->value);
        if (! is_array($stat)) {
            throw new RuntimeException('Unable to retrieve stream statistics.');
        }

        $this->size = $stat['size'];
    }

    public function close(): void
    {
        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! fclose($this->resource->value)) {
            throw new RuntimeException('Failed to close the stream.');
        }

        $this->detach();
    }

    public function detach(): ?StreamResource
    {
        $currentResource = $this->resource;

        $this->resource = null;
        $this->isReadable = false;
        $this->isWritable = false;
        $this->isSeekable = false;
        $this->size = null;

        return $currentResource;
    }

    public function write(string $data): bool
    {
        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->isWritable) {
            throw new RuntimeException('Stream is not writable.');
        }

        if (in_array(fwrite($this->resource->value, $data), [0, false], true)) {
            throw new RuntimeException('Failed to write data to the stream.');
        }

        $this->size = null;

        return true;
    }

    public function rewind(): void
    {
        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->isSeekable) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (! rewind($this->resource->value)) {
            throw new RuntimeException('Failed to rewind the stream.');
        }
    }

    public function readAll(): string
    {
        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->isReadable) {
            throw new RuntimeException('Stream is not readable.');
        }

        $data = stream_get_contents($this->resource->value);
        if (! is_string($data)) {
            throw new RuntimeException('Failed to read data from the stream.');
        }

        return $data;
    }

    public function isFinished(): bool
    {
        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        return feof($this->resource->value);
    }

    public function read(int $length): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Length must be greater than 0.');
        }

        if (! $this->resource instanceof StreamResource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->isReadable) {
            throw new RuntimeException('Stream is not readable.');
        }

        $data = fread($this->resource->value, $length);
        if (! is_string($data)) {
            throw new RuntimeException('Failed to read data from the stream.');
        }

        return $data;
    }
}
