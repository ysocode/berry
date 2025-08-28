<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\DirPath;
use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\MimeType;
use YSOCode\Berry\Domain\ValueObjects\StreamResource;
use YSOCode\Berry\Domain\ValueObjects\TargetFilePath;
use YSOCode\Berry\Domain\ValueObjects\UploadStatus;
use YSOCode\Berry\Infra\Http\UploadedFile;
use YSOCode\Berry\Infra\Stream\Stream;

final class UploadedFileTest extends TestCase
{
    private function createTempFile(): string
    {
        $tempDir = sys_get_temp_dir();

        return tempnam($tempDir, 'test_');
    }

    public function test_it_should_create_a_valid_uploaded_file(): void
    {
        $tempFilePath = $this->createTempFile();

        try {
            $resource = fopen($tempFilePath, 'w+b');
            if (! is_resource($resource)) {
                throw new RuntimeException('Failed to open test stream.');
            }

            $stream = new Stream(new StreamResource($resource));
            $stream->write('Hello, world!');

            $uploadedFile = new UploadedFile(
                $stream,
                UploadStatus::OK,
                new FileName('test.txt'),
                new MimeType('text/plain'),
            );

            $this->assertEquals('Hello, world!', (string) $uploadedFile->stream);
            $this->assertEquals(UploadStatus::OK, $uploadedFile->status);
            $this->assertEquals(new FileName('test.txt'), $uploadedFile->name);
            $this->assertEquals(new MimeType('text/plain'), $uploadedFile->type);
        } finally {
            unlink($tempFilePath);
        }
    }

    public function test_it_should_move_an_uploaded_file(): void
    {
        $tempFilePath = $this->createTempFile();

        $tempDir = sys_get_temp_dir();
        $targetFile = 'copy-test.txt';
        $targetFilePath = $tempDir.'/'.$targetFile;

        try {
            $resource = fopen($tempFilePath, 'w+b');
            if (! is_resource($resource)) {
                throw new RuntimeException('Failed to open test stream.');
            }

            $stream = new Stream(new StreamResource($resource));
            $stream->write('Hello, world!');

            $uploadedFile = new UploadedFile(
                $stream,
                UploadStatus::OK,
                new FileName('test.txt'),
                new MimeType('text/plain'),
            );

            $uploadedFile->moveTo(
                new TargetFilePath(
                    new DirPath($tempDir),
                    new FileName($targetFile)
                )
            );

            $resource = fopen($targetFilePath, 'r+b');
            if (! is_resource($resource)) {
                throw new RuntimeException('Failed to open copy stream.');
            }

            $copyStream = new Stream(new StreamResource($resource));

            $this->assertEquals('Hello, world!', $copyStream->readAll());
        } finally {
            unlink($tempFilePath);
            unlink($targetFilePath);
        }
    }
}
