<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;
use YSOCode\Berry\Infra\Stream\Stream;

final class StreamTest extends TestCase
{
    public function test_it_should_accept_valid_stream_resource(): void
    {
        $resource = fopen('php://temp', 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open temporary stream.');
        }

        $stream = new Stream(new StreamResource($resource));

        try {
            $this->assertInstanceOf(StreamResource::class, $stream->resource);
            $this->assertTrue($stream->isReadable);
            $this->assertTrue($stream->isWritable);
            $this->assertTrue($stream->isSeekable);
            $this->assertIsInt($stream->size);
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_close_stream_and_detach_resource(): void
    {
        $resource = fopen('php://temp', 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open temporary stream.');
        }

        $stream = new Stream(new StreamResource($resource));
        $stream->close();

        $this->assertNull($stream->resource);
        $this->assertFalse($stream->isReadable);
        $this->assertFalse($stream->isWritable);
        $this->assertFalse($stream->isSeekable);
        $this->assertNull($stream->size);
    }

    public function test_it_should_write_data_to_stream_resource(): void
    {
        $resource = fopen('php://temp', 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open temporary stream.');
        }

        $stream = new Stream(new StreamResource($resource));
        $stream->write('Hello, World!');
        $stream->rewind();

        try {
            $this->assertInstanceOf(StreamResource::class, $stream->resource);
            $this->assertEquals('Hello, World!', $stream->readAll());
            $this->assertNull($stream->size);
            $this->assertTrue($stream->isFinished());
        } finally {
            $stream->close();
        }
    }
}
