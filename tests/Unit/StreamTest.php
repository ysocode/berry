<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\Resource;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;
use YSOCode\Berry\Infra\Stream\Stream;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class StreamTest extends TestCase
{
    public function test_it_should_accept_valid_resource(): void
    {
        $this->expectNotToPerformAssertions();

        $resource = @fopen('php://temp', 'rw+');
        if ($resource === false) {
            throw new RuntimeException('Failed to open temporary stream');
        }

        fwrite($resource, 'Hello, Stream!');
        rewind($resource);

        new Stream(new Resource($resource));
    }

    public function test_it_should_read_and_write_data(): void
    {
        $stream = new StreamFactory()->createFromString('Hello, Stream!');

        $bytesWritten = $stream->write('abc');
        $this->assertSame(3, $bytesWritten);

        $stream->rewind();

        $data = $stream->read(5);
        $this->assertSame('abclo', $data);
    }

    public function test_it_should_seek_and_rewind(): void
    {
        $stream = new StreamFactory()->createFromString('Hello, Stream!');

        $stream->seek(6);
        $this->assertSame(6, $stream->tell());

        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function test_it_should_get_contents_and_metadata(): void
    {
        $stream = new StreamFactory()->createFromString('Hello, Stream!');

        $this->assertSame('Hello, Stream!', (string) $stream);
        $this->assertEquals(StreamMode::WRITE_READ_BINARY, $stream->mode);
    }

    public function test_it_should_detect_eof_and_allow_detach(): void
    {
        $stream = new StreamFactory()->createFromString('Hello, Stream!');

        $stream->read(1024);
        $this->assertTrue($stream->eof());

        $detached = $stream->detach();
        $this->assertInstanceOf(Resource::class, $detached);

        $this->expectException(RuntimeException::class);
        $stream->tell();
    }

    public function test_it_should_return_string_representation_and_close(): void
    {
        $stream = new StreamFactory()->createFromString('Hello, Stream!');

        $this->assertSame('Hello, Stream!', (string) $stream);

        $stream->close();

        $this->expectException(RuntimeException::class);
        $stream->tell();
    }
}
