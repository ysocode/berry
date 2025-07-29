<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra;

use YSOCode\Berry\Domain\ValueObjects\Status;

final readonly class Response
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public Status $status,
        public ?string $body = null,
        public array $headers = []
    ) {}

    public function withHeader(string $name, string $value): self
    {
        $newHeaders = $this->headers;
        $newHeaders[$name] = $value;

        return new self($this->status, $this->body, $newHeaders);
    }

    public function withStatus(Status $status): self
    {
        return new self($status, $this->body, $this->headers);
    }
}
