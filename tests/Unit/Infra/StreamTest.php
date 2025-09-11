<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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
        $stream = $this->createStream();

        $refStream = new ReflectionClass($stream);

        $refIsWritable = $refStream->getProperty('isWritable');
        $refIsWritable->setValue($stream, false);

        $refIsSeekable = $refStream->getProperty('isSeekable');
        $refIsSeekable->setValue($stream, false);

        return $stream;
    }

    private function createNotReadableStream(): Stream
    {
        $stream = $this->createStream();

        $refStream = new ReflectionClass($stream);

        $refIsReadable = $refStream->getProperty('isReadable');
        $refIsReadable->setValue($stream, false);

        $refIsSeekable = $refStream->getProperty('isSeekable');
        $refIsSeekable->setValue($stream, false);

        return $stream;
    }

    public function test_it_should_create_a_valid_stream(): void
    {
        $resource = fopen('php://memory', 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open memory stream.');
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
            $stream->write('Hello, world!');
            $stream->rewind();

            $this->assertEquals('Hello, world!', $stream->readAll());
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
            $stream->write('Hello, world!');
            $stream->rewind();

            $this->assertEquals('Hello, world!', $stream->readAll());
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
            $stream->write('Hello, world!');

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
            $stream->write('Hello, world!');
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
            $stream->write('Hello, world!');
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
            $stream->write('Hello, world!');
            $stream->rewind();

            $this->assertEquals('Hello, world!', $stream->readAll());

            $stream->rewind();

            $this->assertEquals('Hello, world!', $stream->readAll());
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_rewind_a_non_seekable_stream(): void
    {
        $stream = $this->createNotReadableStream();

        try {
            $stream->write('Hello, world!');

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
            $stream->write('Hello, world!');
            $stream->seek(7);

            $this->assertEquals('world', $stream->read(5));
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_seek_to_an_exact_position_in_a_non_seekable_stream(): void
    {
        $stream = $this->createNotReadableStream();

        try {
            $stream->write('Hello, world!');

            $this->assertFalse($stream->isSeekable);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Stream is not seekable.');

            $stream->seek(4);
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_tell_the_current_position_in_stream(): void
    {
        $stream = $this->createStream();

        try {
            $stream->write('Hello, world!');
            $stream->seek(2);

            $this->assertEquals(2, $stream->tell());
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_not_tell_the_current_position_in_an_already_closed_stream(): void
    {
        $stream = $this->createStream();
        $stream->write('Hello, world!');
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached.');

        $stream->tell();
    }
}
