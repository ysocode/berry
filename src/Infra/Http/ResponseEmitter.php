<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Stream\Stream;

readonly class ResponseEmitter
{
    public function __construct(private int $chunkSize = 8192) {}

    public function emit(Response $response): void
    {
        if (! headers_sent()) {
            $this->emitStatusLine($response);
            $this->emitHeaders($response);
        }

        if (! $this->isEmpty($response)) {
            $this->emitBody($response);
        }
    }

    protected function emitStatusLine(Response $response): void
    {
        $statusLine = sprintf(
            'HTTP/%s %d %s',
            $response->protocolVersion,
            $response->status->value,
            $response->status->reason()
        );

        header($statusLine, true, $response->status->value);
    }

    protected function emitHeaders(Response $response): void
    {
        foreach ($response->headers as $name => $header) {
            $replace = strtolower($name) !== 'set-cookie';

            header(sprintf('%s: %s', $name, implode(', ', $header->value)), $replace);
        }
    }

    private function emitBody(Response $response): void
    {
        $body = $response->body;

        if (! $body instanceof Stream) {
            return;
        }

        if ($body->isSeekable) {
            $body->rewind();
        }

        $amountToRead = $this->getContentLength($response);

        if ($amountToRead !== null && $amountToRead > 0) {
            while (! $body->eof() && $amountToRead > 0) {
                $toRead = min($this->chunkSize, $amountToRead);
                if ($toRead < 1) {
                    break;
                }

                $chunk = $body->read($toRead);

                echo $chunk;

                $amountToRead -= strlen($chunk);

                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (! $body->eof()) {
                if ($this->chunkSize < 1) {
                    break;
                }

                echo $body->read($this->chunkSize);

                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    private function getContentLength(Response $response): ?int
    {
        $contentLength = $response->getHeader(new HeaderName('Content-Length'));

        if (! $contentLength instanceof Header) {
            return $response->body?->size;
        }

        [$length] = $contentLength->value;

        return (int) $length;
    }

    private function isEmpty(Response $response): bool
    {
        if (
            in_array(
                $response->status,
                [Status::NO_CONTENT, Status::RESET_CONTENT, Status::NOT_MODIFIED],
                true
            )
        ) {
            return true;
        }

        $body = $response->body;

        if (! $body instanceof Stream) {
            return true;
        }

        if ($body->isSeekable) {
            $body->rewind();

            return $body->read(1) === '';
        }

        return $body->eof();
    }
}
