<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\MimeType;
use YSOCode\Berry\Domain\ValueObjects\UploadStatus;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class UploadedFileFactory
{
    /**
     * @param  array{name: string, full_path: string, type: string, tmp_name: string, error: int, size: int}  $spec
     */
    public function createFromSpec(array $spec): UploadedFile
    {
        $stream = null;
        if (FilePath::isValid($spec['tmp_name'])) {
            $stream = new StreamFactory()->createFromFile(new FilePath($spec['tmp_name']));
        }

        $name = null;
        if (FileName::isValid($spec['name'])) {
            $name = new FileName($spec['name']);
        }

        $type = null;
        if (MimeType::isValid($spec['type'])) {
            $type = new MimeType($spec['type']);
        }

        return new UploadedFile(
            $stream,
            UploadStatus::from($spec['error']),
            $name,
            $type,
            true,
        );
    }

    /**
     * @return array<string, UploadedFile|array<int|string, mixed>>
     */
    public function createFromGlobals(): array
    {
        $uploadedFiles = [];

        /** @var array<string, array{name: string|array<int|string, mixed>, full_path: string|array<int|string, mixed>, type: string|array<int|string, mixed>, tmp_name: string|array<int|string, mixed>, error: int|array<int|string, mixed>, size: int|array<int|string, mixed>}> $_FILES */
        foreach ($_FILES as $name => $value) {
            if (! is_string($value['tmp_name'])) {
                /** @var array{name: array<int|string, mixed>, full_path: array<int|string, mixed>, type: array<int|string, mixed>, tmp_name: array<int|string, mixed>, error: array<int|string, mixed>, size: array<int|string, mixed>} $value */
                $uploadedFiles[$name] = $this->resolveNestedUploadedFiles($value);

                continue;
            }

            /** @var array{name: string, full_path: string, type: string, tmp_name: string, error: int, size: int} $value */
            $uploadedFiles[$name] = $this->createFromSpec($value);
        }

        return $uploadedFiles;
    }

    /**
     * @param  array{name: array<int|string, mixed>, full_path: array<int|string, mixed>, type: array<int|string, mixed>, tmp_name: array<int|string, mixed>, error: array<int|string, mixed>, size: array<int|string, mixed>}  $value
     * @return array<int|string, UploadedFile|array<int|string, mixed>>
     */
    private function resolveNestedUploadedFiles(array $value): array
    {
        $parsedFiles = [];
        foreach (array_keys($value['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $value['tmp_name'][$key],
                'error' => $value['error'][$key],
                'name' => $value['name'][$key],
                'type' => $value['type'][$key],
                'size' => $value['size'][$key],
            ];

            if (! is_string($value['tmp_name'][$key])) {
                /** @var array{name: array<int|string, mixed>, full_path: array<int|string, mixed>, type: array<int|string, mixed>, tmp_name: array<int|string, mixed>, error: array<int|string, mixed>, size: array<int|string, mixed>} $spec */
                $parsedFiles[$key] = $this->resolveNestedUploadedFiles($spec);

                continue;
            }

            /** @var array{name: string, full_path: string, type: string, tmp_name: string, error: int, size: int} $spec */
            $parsedFiles[$key] = $this->createFromSpec($spec);
        }

        return $parsedFiles;
    }
}
