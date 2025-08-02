<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra;

use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\FilePath;
use YSOCode\Berry\Domain\ValueObjects\UploadError;

final class UploadedFile
{
    public private(set) bool $isMoved = false;

    public function __construct(
        private(set) Stream $stream {
            get {
                if ($this->isMoved) {
                    throw new RuntimeException('Stream no longer available after move.');
                }

                return $this->stream;
            }
        },
        public readonly ?int $size = null,
        public readonly ?UploadError $error = null,
        public readonly ?FileName $name = null,
        public readonly ?string $type = null,
    ) {}

    public function moveTo(FilePath $filePath): void
    {
        if ($this->isMoved) {
            throw new RuntimeException('File already moved.');
        }

        $targetPath = (string) $filePath;

        $targetDir = dirname($targetPath);
        if (! is_dir($targetDir) || ! is_writable($targetDir)) {
            throw new RuntimeException("Target directory is not writable: {$targetDir}.");
        }

        $stream = $this->stream;
        if ($stream->isSeekable) {
            $stream->rewind();
        }

        $resource = @fopen($targetPath, 'wb');
        if (! $resource) {
            throw new RuntimeException(
                sprintf('Cannot open target path "%s" for writing.', $targetPath)
            );
        }

        while (! $stream->eof()) {
            $bytes = $stream->read(8192);
            if (@fwrite($resource, $bytes) === false) {
                @fclose($resource);

                throw new RuntimeException("Failed to write to target file: {$targetPath}.");
            }
        }

        @fclose($resource);

        $this->isMoved = true;
    }
}
