<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\MimeType;
use YSOCode\Berry\Domain\ValueObjects\TargetFilePath;
use YSOCode\Berry\Domain\ValueObjects\UploadStatus;
use YSOCode\Berry\Infra\Stream\Stream;

final class UploadedFile
{
    public private(set) bool $isMoved = false;

    public function __construct(
        public readonly ?Stream $stream,
        public readonly UploadStatus $status,
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

        if (! is_string($this->stream->uri)) {
            throw new RuntimeException('Stream URI must be a string.');
        }

        try {
            if ($this->fromWebServer) {
                if (! is_uploaded_file($this->stream->uri)) {
                    throw new RuntimeException('Invalid uploaded file.');
                }
                if (! move_uploaded_file($this->stream->uri, (string) $targetFilePath)) {
                    throw new RuntimeException('Failed to move uploaded file.');
                }
            } elseif (! copy($this->stream->uri, (string) $targetFilePath)) {
                throw new RuntimeException('Failed to copy uploaded file.');
            }

            $this->isMoved = true;
        } finally {
            $this->stream->close();
        }
    }
}
