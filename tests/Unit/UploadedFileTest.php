<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\StreamMode;
use YSOCode\Berry\Infra\Http\UploadedFile;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class UploadedFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/upload_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir.'/*');
        if ($files === false) {
            throw new RuntimeException('Failed to list files in temp directory.');
        }

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($this->tempDir);

        parent::tearDown();
    }

    public function test_it_should_write_file_and_mark_as_moved(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);

        $uploadedFile = new UploadedFile($stream);

        $destFile = $this->tempDir.'/dest.txt';
        $filePath = new FilePath($destFile);

        $uploadedFile->moveTo($filePath);

        $this->assertTrue($uploadedFile->isMoved);
        $this->assertFileExists($destFile);
        $this->assertStringEqualsFile($destFile, 'Conteúdo do arquivo.');
    }

    public function test_it_should_throw_when_accessing_stream_after_move(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);

        $uploadedFile = new UploadedFile($stream);

        $filePath = new FilePath($this->tempDir.'/dest.txt');
        $uploadedFile->moveTo($filePath);

        $this->expectException(RuntimeException::class);

        /** @phpstan-ignore-next-line */
        $uploadedFile->stream;
    }

    public function test_it_should_throw_when_moving_twice(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);
        $uploadedFile = new UploadedFile($stream);

        $filePath = new FilePath($this->tempDir.'/dest.txt');
        $uploadedFile->moveTo($filePath);

        $this->expectException(RuntimeException::class);

        $uploadedFile->moveTo($filePath);
    }

    public function test_it_should_throw_when_moving_to_unwritable_directory(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);

        $uploadedFile = new UploadedFile($stream);

        $badDir = $this->tempDir.'/notExist';
        $filePath = new FilePath($badDir.'/file.txt');

        $this->expectException(RuntimeException::class);

        $uploadedFile->moveTo($filePath);
    }

    public function test_it_should_move_to_same_path_successfully(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);
        $uploadedFile = new UploadedFile($stream);

        $filePath = new FilePath($sourceFile);
        $uploadedFile->moveTo($filePath);

        $this->assertTrue($uploadedFile->isMoved);
        $this->assertFileExists($sourceFile);
    }

    public function test_it_should_throw_when_stream_is_closed_before_move(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);
        $stream->close();

        $uploadedFile = new UploadedFile($stream);
        $filePath = new FilePath($this->tempDir.'/dest.txt');

        $this->expectException(RuntimeException::class);

        $uploadedFile->moveTo($filePath);
    }

    public function test_it_should_preserve_file_content_after_move(): void
    {
        $sourceFile = $this->tempDir.'/source.txt';
        file_put_contents($sourceFile, 'Conteúdo do arquivo.');

        $stream = new StreamFactory()->createFromFile(new FilePath($sourceFile), StreamMode::READ_WRITE_BINARY);
        $uploadedFile = new UploadedFile($stream);

        $filePath = new FilePath($this->tempDir.'/moved.txt');
        $uploadedFile->moveTo($filePath);

        $this->assertStringEqualsFile($filePath->value, 'Conteúdo do arquivo.');
    }
}
