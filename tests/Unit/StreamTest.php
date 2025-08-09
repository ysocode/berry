<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;
use YSOCode\Berry\Infra\Stream\Stream;

final class StreamTest extends TestCase
{
    private function createStream(): Stream
    {
        $resource = fopen('php://temp', 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open temporary stream.');
        }

        return new Stream(new StreamResource($resource));
    }

    private function createNotWritableStream(): Stream
    {
        $resource = fopen('php://stdin', 'rb');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open stdin stream.');
        }

        return new Stream(new StreamResource($resource));
    }

    private function createNotReadableStream(): Stream
    {
        $resource = fopen('php://stdout', 'wb');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open stdout stream.');
        }

        return new Stream(new StreamResource($resource));
    }

    public function test_it_should_initialize_with_a_valid_stream(): void
    {
        $stream = $this->createStream();

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

    public function test_it_should_close_stream(): void
    {
        $stream = $this->createStream();
        $stream->close();

        $this->assertNull($stream->resource);
        $this->assertFalse($stream->isReadable);
        $this->assertFalse($stream->isWritable);
        $this->assertFalse($stream->isSeekable);
        $this->assertNull($stream->size);
    }

    public function test_it_should_not_close_an_already_closed_stream(): void
    {
        $stream = $this->createStream();
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached.');

        $stream->close();
    }

    public function test_it_should_write_to_stream(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, World!');
            $stream->rewind();

            $this->assertEquals('Hello, World!', $stream->readAll());
            $this->assertTrue($stream->isFinished());
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_write_to_an_already_closed_stream(): void
    {
        $stream = $this->createStream();
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached.');

        $stream->write('This should fail.');
    }

    public function test_it_should_not_write_to_an_only_readable_stream(): void
    {
        $stream = $this->createNotWritableStream();

        try {
            $this->assertTrue($stream->isReadable);
            $this->assertFalse($stream->isWritable);
            $this->assertFalse($stream->isSeekable);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Stream is not writable.');

            $stream->write('This should fail.');
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_read_all_from_stream(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, World!');
            $stream->rewind();

            $this->assertEquals('Hello, World!', $stream->readAll());
            $this->assertTrue($stream->isFinished());
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_read_all_from_an_already_closed_stream(): void
    {
        $stream = $this->createStream();
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached.');

        $stream->readAll();
    }

    public function test_it_should_not_read_all_from_an_only_writable_stream(): void
    {
        $stream = $this->createNotReadableStream();

        try {
            $stream->write('Hello, World!');

            $this->assertFalse($stream->isReadable);
            $this->assertTrue($stream->isWritable);
            $this->assertFalse($stream->isSeekable);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Stream is not readable.');

            $stream->readAll();
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_read_exact_number_of_requested_bytes_from_stream(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, World!');
            $stream->rewind();

            $this->assertEquals('He', $stream->read(2));
            $this->assertFalse($stream->isFinished());
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_read_exact_number_of_requested_bytes_from_an_already_closed_stream(): void
    {
        $stream = $this->createStream();
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached.');

        $stream->read(2);
    }

    public function test_it_should_not_read_when_requested_bytes_is_zero_or_negative(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, World!');
            $stream->rewind();

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Length must be greater than 0.');

            $stream->read(0);
            $stream->read(-1);
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_rewind_stream(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, World!');
            $stream->rewind();

            $this->assertEquals('Hello, World!', $stream->readAll());

            $stream->rewind();

            $this->assertEquals('Hello, World!', $stream->readAll());
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_rewind_a_non_seekable_stream(): void
    {
        $stream = $this->createNotReadableStream();

        try {
            $stream->write('Hello, World!');

            $this->assertFalse($stream->isSeekable);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Stream is not seekable.');

            $stream->rewind();
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_seek_to_an_exact_position_in_stream(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, World!');
            $stream->seek(7);

            $this->assertEquals('World', $stream->read(5));
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_seek_to_an_exact_position_in_a_non_seekable_stream(): void
    {
        $stream = $this->createNotReadableStream();

        try {
            $stream->write('Hello, World!');

            $this->assertFalse($stream->isSeekable);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Stream is not seekable.');

            $stream->seek(4);
        } finally {
            $stream->close();
        }
    }
}
