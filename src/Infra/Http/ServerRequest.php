<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\Attribute;
use YSOCode\Berry\Domain\ValueObjects\AttributeName;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
use YSOCode\Berry\Infra\Stream\Stream;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ServerRequest
{
    use MessageTrait;
    use RequestTrait;

    /**
     * @var array<string, Attribute>
     */
    public private(set) array $attributes = [];

    /**
     * @param  array<Header>  $headers
     * @param  array<string, mixed>  $serverParams
     * @param  array<string, string>  $cookieParams
     * @param  array<string, string|array<int|string, mixed>>  $queryParams
     * @param  array<string, string|array<int|string, mixed>>  $parsedBody
     * @param  array<string, UploadedFile|array<int|string, mixed>>  $uploadedFiles
     * @param  array<Attribute>  $attributes
     */
    public function __construct(
        HttpMethod $method,
        Uri $uri,
        array $headers = [],
        ?Stream $body = null,
        private(set) array $serverParams = [],
        private(set) array $cookieParams = [],
        private(set) array $queryParams = [],
        private(set) array $parsedBody = [],
        private(set) array $uploadedFiles = [],
        array $attributes = [],
        HttpVersion $version = new HttpVersion('1.1'),
    ) {
        $this->method = $method;

        $this->uri = $uri;

        $this->setTarget($uri);

        $this->body = $body ?? new StreamFactory()->createFromString();

        $this->setHeaders($headers);

        $this->setAttributes($attributes);

        $this->version = $version;
    }

    /**
     * @param  array<Attribute>  $attributes
     */
    private function setAttributes(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->attributes[(string) $attribute->name] = $attribute;
        }
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
     * @param  array<string, string|array<int|string, mixed>>  $queryParams
     */
    public function withQueryParams(array $queryParams): self
    {
        $new = clone $this;
        $new->queryParams = $queryParams;

        return $new;
    }

    /**
     * @param  array<string, string|array<int|string, mixed>>  $parsedBody
     */
    public function withParsedBody(array $parsedBody): self
    {
        $new = clone $this;
        $new->parsedBody = $parsedBody;

        return $new;
    }

    /**
     * @param  array<string, UploadedFile|array<int|string, mixed>>  $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    public function hasAttribute(AttributeName $name): bool
    {
        return isset($this->attributes[(string) $name]);
    }

    public function getAttribute(AttributeName $name): ?Attribute
    {
        return $this->attributes[(string) $name] ?? null;
    }

    public function withAttribute(Attribute $attribute): self
    {
        $new = clone $this;
        $new->attributes[(string) $attribute->name] = $attribute;

        return $new;
    }

    public function withoutAttribute(AttributeName $name): self
    {
        $new = clone $this;
        unset($new->attributes[(string) $name]);

        return $new;
    }
}
