<?php

declare(strict_types=1);

namespace Tests\Factory;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class StreamFactoryTest extends TestCase
{
    private function createTempFile(): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFilePath = $tempDir.'/test.txt';

        touch($tempFilePath);

        return $tempFilePath;
    }

    public function test_it_should_create_a_stream_from_string(): void
    {
        $stream = new StreamFactory()->createFromString('Hello, World!');

        try {
            $this->assertInstanceOf(StreamResource::class, $stream->resource);
            $this->assertEquals('Hello, World!', $stream->readAll());
            $this->assertTrue($stream->isReadable);
            $this->assertTrue($stream->isWritable);
            $this->assertTrue($stream->isSeekable);
            $this->assertNull($stream->size);
        } finally {
            $stream->close();
        }
    }

    public function test_it_should_create_a_stream_from_file(): void
    {
        $tempFilePath = $this->createTempFile();
        $stream = new StreamFactory()->createFromFile(new FilePath($tempFilePath));

        try {
            $stream->write('Hello, World!');
            $stream->rewind();

            $this->assertInstanceOf(StreamResource::class, $stream->resource);
            $this->assertEquals('Hello, World!', $stream->readAll());
            $this->assertTrue($stream->isReadable);
            $this->assertTrue($stream->isWritable);
            $this->assertTrue($stream->isSeekable);
            $this->assertNull($stream->size);
        } finally {
            $stream->close();

            unlink($tempFilePath);
        }
    }

    public function test_it_should_create_a_stream_from_file_with_non_exclusive_modes(): void
    {
        $tempFilePath = $this->createTempFile();

        try {
            $exclusiveModes = [
                StreamMode::EXCLUSIVE_CREATE,
                StreamMode::EXCLUSIVE_CREATE_READ,
                StreamMode::EXCLUSIVE_CREATE_BINARY,
                StreamMode::EXCLUSIVE_CREATE_READ_BINARY,
            ];

            foreach (StreamMode::cases() as $mode) {
                if (in_array($mode, $exclusiveModes)) {
                    continue;
                }

                $stream = new StreamFactory()->createFromFile(new FilePath($tempFilePath), $mode);
                $this->assertInstanceOf(StreamResource::class, $stream->resource);
                $stream->close();
            }
        } finally {
            unlink($tempFilePath);
        }
    }
}
