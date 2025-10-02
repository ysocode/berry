<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\MimeType;
use YSOCode\Berry\Domain\ValueObjects\TargetFilePath;
use YSOCode\Berry\Domain\ValueObjects\UploadFileStatus;
use YSOCode\Berry\Infra\Stream\Stream;

final class UploadedFile
{
    public private(set) bool $isMoved = false;

    public function __construct(
        public readonly ?Stream $stream,
        public readonly UploadFileStatus $status,
        public readonly ?FileName $name = null,
        public readonly ?MimeType $type = null,
        public readonly bool $fromWebServer = false,
    ) {}

    public function moveTo(TargetFilePath $targetFilePath): void
    {
        if ($this->isMoved) {
            throw new RuntimeException('Uploaded file has already been moved.');
        }

        if ($this->status->isError()) {
            throw new RuntimeException($this->status->getMessage());
        }

        if (! $this->stream instanceof Stream || ! $this->stream->isAttached()) {
            throw new RuntimeException('No stream available for uploaded file.');
        }

        $uri = $this->stream->meta['uri'] ?? null;
        if (! is_string($uri)) {
            throw new RuntimeException('Stream URI is missing or invalid.');
        }

        try {
            if ($this->fromWebServer) {
                if (! is_uploaded_file($uri)) {
                    throw new RuntimeException('Invalid uploaded file.');
                }
                if (! move_uploaded_file($uri, (string) $targetFilePath)) {
                    throw new RuntimeException('Failed to move uploaded file.');
                }
            } elseif (! copy($uri, (string) $targetFilePath)) {
                throw new RuntimeException('Failed to copy uploaded file.');
            }

            $this->isMoved = true;
        } finally {
            $this->stream->close();
        }
    }
}
