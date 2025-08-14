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
    public function test_it_should_create_a_valid_uploaded_file(): void
    {
        $tempDir = sys_get_temp_dir();
        $testFile = 'test.txt';

        $testFilePath = $tempDir.'/'.$testFile;

        touch($testFilePath);

        $resource = fopen($testFilePath, 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open test stream.');
        }

        $stream = new Stream(new StreamResource($resource));
        $stream->write('Hello, World!');

        $uploadedFile = new UploadedFile(
            $stream,
            UploadStatus::OK,
            new FileName('test.txt'),
            new MimeType('text/plain'),
        );

        $this->assertEquals('Hello, World!', (string) $uploadedFile->stream);
        $this->assertEquals(UploadStatus::OK, $uploadedFile->status);
        $this->assertEquals(new FileName('test.txt'), $uploadedFile->name);
        $this->assertEquals(new MimeType('text/plain'), $uploadedFile->type);

        unlink($testFilePath);
    }

    public function test_it_should_move_an_uploaded_file(): void
    {
        $tempDir = sys_get_temp_dir();
        $testFile = 'test.txt';
        $copyTestFile = 'copy-test.txt';

        $testFilePath = $tempDir.'/'.$testFile;
        $copyTestFilePath = $tempDir.'/'.$copyTestFile;

        touch($testFilePath);

        $resource = fopen($testFilePath, 'w+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open test stream.');
        }

        $stream = new Stream(new StreamResource($resource));
        $stream->write('Hello, World!');

        $uploadedFile = new UploadedFile(
            $stream,
            UploadStatus::OK,
            new FileName('test.txt'),
            new MimeType('text/plain'),
        );

        $uploadedFile->moveTo(
            new TargetFilePath(
                new DirPath($tempDir),
                new FileName($copyTestFile)
            )
        );

        $resource = fopen($copyTestFilePath, 'r+b');
        if (! is_resource($resource)) {
            throw new RuntimeException('Failed to open copy stream.');
        }

        $copyStream = new Stream(new StreamResource($resource));

        $this->assertEquals('Hello, World!', $copyStream->readAll());

        unlink($testFilePath);
        unlink($copyTestFilePath);
    }
}
