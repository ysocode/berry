<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use InvalidArgumentException;
use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Infra\Stream\Stream;

final class ServerRequest
{
    /**
     * @var array<string, UploadedFile>
     */
    public private(set) array $uploadedFiles = [];

    /**
     * @param  array<string, string>  $serverParams
     * @param  array<string, string>  $cookieParams
     * @param  array<string, mixed>  $queryParams
     * @param  array<string, mixed>  $parsedBody
     * @param  array<UploadedFile>  $uploadedFiles
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        private Request $request,
        private(set) array $serverParams = [],
        private(set) array $cookieParams = [],
        private(set) array $queryParams = [],
        private(set) array $parsedBody = [],
        array $uploadedFiles = [],
        private(set) array $attributes = []
    ) {
        $this->setUploadedFiles($uploadedFiles);
    }

    /**
     * @param  array<UploadedFile>  $uploadedFiles
     */
    private function setUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $uploadedFile) {
            if (! $uploadedFile instanceof UploadedFile) {
                throw new InvalidArgumentException('Each uploaded file must be an instance of UploadedFile.');
            }

            $this->uploadedFiles[(string) $uploadedFile->name] = $uploadedFile;
        }
    }

    public function getHeader(HeaderName $name): ?Header
    {
        return $this->request->getHeader($name);
    }

    public function hasHeader(HeaderName $name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function withHeader(Header $header): self
    {
        $new = clone $this;
        $new->request = $this->request->withHeader($header);

        return $new;
    }

    public function withoutHeader(HeaderName $name): self
    {
        $new = clone $this;
        $new->request = $this->request->withoutHeader($name);

        return $new;
    }

    public function withBody(Stream $body): self
    {
        $new = clone $this;
        $new->request = $this->request->withBody($body);

        return $new;
    }

    public function withMethod(Method $method): self
    {
        $new = clone $this;
        $new->request = $this->request->withMethod($method);

        return $new;
    }

    public function withUri(Uri $uri): self
    {
        $new = clone $this;
        $new->request = $this->request->withUri($uri);

        return $new;
    }

    /**
     * @param  array<string, string>  $serverParams
     */
    public function withServerParams(array $serverParams): self
    {
        $new = clone $this;
        $new->serverParams = $serverParams;

        return $new;
    }

    /**
     * @param  array<string, string>  $cookieParams
     */
    public function withCookieParams(array $cookieParams): self
    {
        $new = clone $this;
        $new->cookieParams = $cookieParams;

        return $new;
    }

    /**
     * @param  array<string, mixed>  $queryParams
     */
    public function withQueryParams(array $queryParams): self
    {
        $new = clone $this;
        $new->queryParams = $queryParams;

        return $new;
    }

    public function getUploadedFile(FileName $name): ?UploadedFile
    {
        return $this->uploadedFiles[(string) $name] ?? null;
    }

    /**
     * @param  array<UploadedFile>  $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;

        $new->uploadedFiles = [];

        $new->setUploadedFiles($uploadedFiles);

        return $new;
    }

    /**
     * @param  array<string, mixed>  $parsedBody
     */
    public function withParsedBody(array $parsedBody): self
    {
        $new = clone $this;
        $new->parsedBody = $parsedBody;

        return $new;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute(string $name): self
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }
}
