<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

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
    /**
     * @return array{Stream, string}
     */
    private function createStream(): array
    {
        $tempDir = sys_get_temp_dir();
        $tempFilePath = tempnam($tempDir, 'test_');

        $resource = fopen($tempFilePath, 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open test stream.');
        }

        return [
            new Stream(new StreamResource($resource)),
            $tempFilePath,
        ];
    }

    /**
     * @return array{string, string, string}
     */
    private function getTargetFilePathParts(): array
    {
        $tempDir = sys_get_temp_dir();
        $targetFile = 'copy-test.txt';
        $targetFilePath = $tempDir.'/'.$targetFile;

        return [$tempDir, $targetFile, $targetFilePath];
    }

    public function test_it_should_create_a_valid_uploaded_file(): void
    {
        [$stream, $tempFilePath] = $this->createStream();

        try {
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
        [$stream, $tempFilePath] = $this->createStream();
        [$tempDir, $targetFile, $targetFilePath] = $this->getTargetFilePathParts();

        try {
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

    public function test_it_should_not_move_an_uploaded_file_when_already_moved(): void
    {
        [$stream, $tempFilePath] = $this->createStream();
        [$tempDir, $targetFile, $targetFilePath] = $this->getTargetFilePathParts();

        try {
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

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Uploaded file has already been moved.');

            $uploadedFile->moveTo(
                new TargetFilePath(
                    new DirPath($tempDir),
                    new FileName('new-copy-test.txt')
                )
            );
        } finally {
            unlink($tempFilePath);
            unlink($targetFilePath);
        }
    }

    public function test_it_should_not_move_an_uploaded_file_when_error_exists(): void
    {
        [$tempDir, $targetFile] = $this->getTargetFilePathParts();

        $uploadStatus = UploadStatus::NO_FILE;

        $uploadedFile = new UploadedFile(
            null,
            $uploadStatus,
            null,
            null,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($uploadStatus->getMessage());

        $uploadedFile->moveTo(
            new TargetFilePath(
                new DirPath($tempDir),
                new FileName($targetFile)
            )
        );
    }

    public function test_it_should_not_move_an_uploaded_file_when_stream_is_not_available(): void
    {
        [$stream, $tempFilePath] = $this->createStream();
        [$tempDir, $targetFile] = $this->getTargetFilePathParts();

        try {
            $stream->close();

            $uploadedFile = new UploadedFile(
                $stream,
                UploadStatus::OK,
                new FileName('test.txt'),
                new MimeType('text/plain'),
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('No stream available for uploaded file.');

            $uploadedFile->moveTo(
                new TargetFilePath(
                    new DirPath($tempDir),
                    new FileName($targetFile)
                )
            );
        } finally {
            unlink($tempFilePath);
        }
    }

    public function test_it_should_not_move_an_uploaded_file_when_falsely_marked_as_from_web_server(): void
    {
        [$stream, $tempFilePath] = $this->createStream();
        [$tempDir, $targetFile] = $this->getTargetFilePathParts();

        try {
            $stream->write('Hello, world!');

            $uploadedFile = new UploadedFile(
                $stream,
                UploadStatus::OK,
                new FileName('test.txt'),
                new MimeType('text/plain'),
                true
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Invalid uploaded file.');

            $uploadedFile->moveTo(
                new TargetFilePath(
                    new DirPath($tempDir),
                    new FileName($targetFile)
                )
            );
        } finally {
            unlink($tempFilePath);
        }
    }
}
