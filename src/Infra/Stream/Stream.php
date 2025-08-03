<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Stream;

use InvalidArgumentException;
use RuntimeException;
use Stringable;
use Throwable;
use YSOCode\Berry\Domain\ValueObjects\Resource;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;

final class Stream implements Stringable
{
    private ?Resource $resource;

    /** @var array<string, mixed>|null */
    private ?array $meta;

    public private(set) ?StreamMode $mode;

    public private(set) bool $isSeekable;

    public private(set) ?int $size;

    public function __construct(Resource $resource)
    {
        $this->extractParts($resource);

        $this->resource = $resource;
    }

    private function extractParts(Resource $resource): void
    {
        if (! is_resource($resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        $stats = @fstat($resource->value);
        if ($stats === false) {
            throw new RuntimeException('Failed to get stream statistics.');
        }

        $this->meta = stream_get_meta_data($resource->value);
        $this->size = $stats['size'];
        $this->isSeekable = $this->meta['seekable'] && @fseek($resource->value, 0, SEEK_CUR) === 0;
        $this->mode = StreamMode::from($this->meta['mode']);
    }

    public function close(): void
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        if (@fclose($this->resource->value) === false) {
            throw new RuntimeException('Failed to close the stream.');
        }

        $this->detach();
    }

    public function detach(): ?Resource
    {
        if (! $this->resource instanceof Resource) {
            return null;
        }

        $oldResource = $this->resource;

        $this->resource = null;
        $this->meta = null;
        $this->mode = null;
        $this->isSeekable = false;
        $this->size = null;

        return $oldResource;
    }

    public function tell(): int
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        $position = @ftell($this->resource->value);
        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        return @feof($this->resource->value);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        if (! $this->isSeekable) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (@fseek($this->resource->value, $offset, $whence) === -1) {
            throw new RuntimeException('Failed to seek stream.');
        }
    }

    public function rewind(): void
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        if (! $this->isSeekable) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (@rewind($this->resource->value) === false) {
            throw new RuntimeException('Failed to rewind stream.');
        }
    }

    public function write(string $data): int
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        if ($this->mode?->isWritable() !== true) {
            throw new RuntimeException('Stream is not writable.');
        }

        $bytes = @fwrite($this->resource->value, $data);
        if ($bytes === false) {
            throw new RuntimeException('Failed to write to stream.');
        }

        return $bytes;
    }

    /**
     * @param  int<1, max>  $length
     */
    public function read(int $length): string
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        if ($this->mode?->isReadable() !== true) {
            throw new RuntimeException('Stream is not readable.');
        }

        $data = @fread($this->resource->value, $length);
        if ($data === false) {
            throw new RuntimeException('Failed to read from stream.');
        }

        return $data;
    }

    public function getContents(): string
    {
        if (! $this->resource instanceof Resource) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! is_resource($this->resource->value)) {
            throw new InvalidArgumentException('Invalid resource provided.');
        }

        if ($this->mode?->isReadable() !== true) {
            throw new RuntimeException('Stream is not readable.');
        }

        $contents = @stream_get_contents($this->resource->value);
        if ($contents === false) {
            throw new RuntimeException('Failed to get stream contents.');
        }

        return $contents;
    }

    public function __toString(): string
    {
        if (! $this->isSeekable) {
            return '';
        }

        try {
            $this->rewind();

            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }
}
