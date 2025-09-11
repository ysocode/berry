<?php

declare(strict_types=1);

namespace Tests\Factory;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\UploadStatus;
use YSOCode\Berry\Infra\Http\UploadedFile;
use YSOCode\Berry\Infra\Http\UploadedFileFactory;

final class UploadedFileFactoryTest extends TestCase
{
    private function createTempFile(): string
    {
        $tempDir = sys_get_temp_dir();

        return tempnam($tempDir, 'test_');
    }

    public function test_it_should_create_uploaded_file_from_spec(): void
    {
        $tempFilePath = $this->createTempFile();

        try {
            $spec = [
                'name' => 'doc.pdf',
                'full_path' => 'doc.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tempFilePath,
                'error' => 0,
                'size' => 85402,
            ];

            $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

            $this->assertEquals(UploadStatus::OK, $uploadedFile->status);
            $this->assertEquals('doc.pdf', (string) $uploadedFile->name);
            $this->assertEquals('application/pdf', (string) $uploadedFile->type);
            $this->assertTrue($uploadedFile->fromWebServer);
        } finally {
            unlink($tempFilePath);
        }
    }

    public function test_it_should_create_uploaded_file_from_simple_global_files(): void
    {
        $tempFilePath = $this->createTempFile();

        $_FILES = [
            'doc' => [
                'name' => 'doc.pdf',
                'full_path' => 'doc.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tempFilePath,
                'error' => 0,
                'size' => 85402,
            ],
        ];

        try {
            $uploadedFiles = new UploadedFileFactory()->createFromGlobals();
            $docUploadedFile = $uploadedFiles['doc'];

            $this->assertInstanceOf(UploadedFile::class, $docUploadedFile);
            $this->assertEquals(UploadStatus::OK, $docUploadedFile->status);
            $this->assertEquals('doc.pdf', (string) $docUploadedFile->name);
            $this->assertEquals('application/pdf', (string) $docUploadedFile->type);
            $this->assertTrue($docUploadedFile->fromWebServer);
        } finally {
            $_FILES = [];

            unlink($tempFilePath);
        }
    }

    public function test_it_should_create_uploaded_file_from_nested_global_files(): void
    {
        $firstTempFilePath = $this->createTempFile();
        $secondTempFilePath = $this->createTempFile();

        $_FILES = [
            'files' => [
                'name' => [
                    'images' => [
                        'image.png',
                    ],
                    'docs' => [
                        'doc.pdf',
                    ],
                ],
                'full_path' => [
                    'images' => [
                        'image.png',
                    ],
                    'docs' => [
                        'doc.pdf',
                    ],
                ],
                'type' => [
                    'images' => [
                        'image/png',
                    ],
                    'docs' => [
                        'application/pdf',
                    ],
                ],
                'tmp_name' => [
                    'images' => [
                        $firstTempFilePath,
                    ],
                    'docs' => [
                        $secondTempFilePath,
                    ],
                ],
                'error' => [
                    'images' => [
                        0,
                    ],
                    'docs' => [
                        0,
                    ],
                ],
                'size' => [
                    'images' => [
                        1455947,
                    ],
                    'docs' => [
                        85402,
                    ],
                ],
            ],
        ];

        try {
            $uploadedFiles = new UploadedFileFactory()->createFromGlobals();

            $files = $uploadedFiles['files'] ?? null;
            if (! is_array($files)) {
                throw new RuntimeException('Expected "files" to be an array.');
            }

            $images = $files['images'] ?? [];
            if (! is_array($images)) {
                throw new RuntimeException('Expected "images" to be an array.');
            }

            $docs = $files['docs'] ?? [];
            if (! is_array($docs)) {
                throw new RuntimeException('Expected "docs" to be an array.');
            }

            $firstUploadedImage = $images[0] ?? null;
            $firstUploadedDoc = $docs[0] ?? null;

            $this->assertInstanceOf(UploadedFile::class, $firstUploadedImage);
            $this->assertInstanceOf(UploadedFile::class, $firstUploadedDoc);
            $this->assertEquals(UploadStatus::OK, $firstUploadedImage->status);
            $this->assertEquals(UploadStatus::OK, $firstUploadedDoc->status);
            $this->assertEquals('image.png', (string) $firstUploadedImage->name);
            $this->assertEquals('doc.pdf', (string) $firstUploadedDoc->name);
            $this->assertEquals('image/png', (string) $firstUploadedImage->type);
            $this->assertEquals('application/pdf', (string) $firstUploadedDoc->type);
            $this->assertTrue($firstUploadedImage->fromWebServer);
            $this->assertTrue($firstUploadedDoc->fromWebServer);
        } finally {
            $_FILES = [];

            unlink($firstTempFilePath);
            unlink($secondTempFilePath);
        }
    }

    public function test_it_should_create_uploaded_file_from_mixed_global_files(): void
    {
        $firstTempFilePath = $this->createTempFile();
        $secondTempFilePath = $this->createTempFile();

        $_FILES = [
            'files' => [
                'name' => [
                    'docs' => [
                        'doc.pdf',
                    ],
                    'image' => 'image.png',
                ],
                'full_path' => [
                    'docs' => [
                        'doc.pdf',
                    ],
                    'image' => 'image.png',
                ],
                'type' => [
                    'docs' => [
                        'application/pdf',
                    ],
                    'image' => 'image/png',
                ],
                'tmp_name' => [
                    'docs' => [
                        $firstTempFilePath,
                    ],
                    'image' => $secondTempFilePath,
                ],
                'error' => [
                    'docs' => [
                        0,
                    ],
                    'image' => 0,
                ],
                'size' => [
                    'docs' => [
                        36492,
                    ],
                    'image' => 1455947,
                ],
            ],
        ];

        try {
            $uploadedFiles = new UploadedFileFactory()->createFromGlobals();

            $files = $uploadedFiles['files'] ?? null;
            if (! is_array($files)) {
                throw new RuntimeException('Expected "files" to be an array.');
            }

            $docs = $files['docs'] ?? [];
            if (! is_array($docs)) {
                throw new RuntimeException('Expected "docs" to be an array.');
            }

            $firstUploadedDoc = $docs[0] ?? null;
            $uploadedImage = $files['image'] ?? null;

            $this->assertInstanceOf(UploadedFile::class, $firstUploadedDoc);
            $this->assertInstanceOf(UploadedFile::class, $uploadedImage);
            $this->assertEquals(UploadStatus::OK, $firstUploadedDoc->status);
            $this->assertEquals(UploadStatus::OK, $uploadedImage->status);
            $this->assertEquals('doc.pdf', (string) $firstUploadedDoc->name);
            $this->assertEquals('image.png', (string) $uploadedImage->name);
            $this->assertEquals('application/pdf', (string) $firstUploadedDoc->type);
            $this->assertEquals('image/png', (string) $uploadedImage->type);
            $this->assertTrue($firstUploadedDoc->fromWebServer);
            $this->assertTrue($uploadedImage->fromWebServer);
        } finally {
            $_FILES = [];

            unlink($firstTempFilePath);
            unlink($secondTempFilePath);
        }
    }

    public function test_it_should_handle_ini_size_error(): void
    {
        $spec = [
            'name' => 'bigFile.zip',
            'full_path' => 'bigFile.zip',
            'type' => 'application/zip',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 999999999,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::INI_SIZE, $uploadedFile->status);
    }

    public function test_it_should_handle_form_size_error(): void
    {
        $spec = [
            'name' => 'bigFile.zip',
            'full_path' => 'bigFile.zip',
            'type' => 'application/zip',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_FORM_SIZE,
            'size' => 999999999,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::FORM_SIZE, $uploadedFile->status);
    }

    public function test_it_should_handle_partial_upload_error(): void
    {
        $spec = [
            'name' => 'video.mp4',
            'full_path' => 'video.mp4',
            'type' => 'video/mp4',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => 123456,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::PARTIAL, $uploadedFile->status);
    }

    public function test_it_should_handle_no_file_error(): void
    {
        $spec = [
            'name' => '',
            'full_path' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::NO_FILE, $uploadedFile->status);
    }

    public function test_it_should_handle_no_tmp_dir_error(): void
    {
        $spec = [
            'name' => 'doc.pdf',
            'full_path' => 'doc.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_TMP_DIR,
            'size' => 123,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::NO_TMP_DIR, $uploadedFile->status);
    }

    public function test_it_should_handle_cant_write_error(): void
    {
        $spec = [
            'name' => 'doc.pdf',
            'full_path' => 'doc.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_CANT_WRITE,
            'size' => 123,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::CANT_WRITE, $uploadedFile->status);
    }

    public function test_it_should_handle_extension_error(): void
    {
        $spec = [
            'name' => 'malicious.php',
            'full_path' => 'malicious.php',
            'type' => 'application/x-php',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_EXTENSION,
            'size' => 123,
        ];

        $uploadedFile = new UploadedFileFactory()->createFromSpec($spec);

        $this->assertEquals(UploadStatus::EXTENSION, $uploadedFile->status);
    }
}
