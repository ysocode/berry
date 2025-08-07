<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Stream;

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
}
