<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Infra\Stream\Stream;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class Request
{
    use MessageTrait;

    public private(set) string $target;

    /**
     * @param  array<Header>  $headers
     */
    public function __construct(
        private(set) HttpMethod $method,
        private(set) Uri $uri,
        array $headers = [],
        ?Stream $body = null,
        HttpVersion $version = new HttpVersion('1.1'),
    ) {
        $this->setTarget($uri);

        $this->body = $body ?? new StreamFactory()->createFromString();

        $this->setHeaders($headers);

        $this->version = $version;
    }

    private function setTarget(Uri $uri): void
    {
        $target = '/';

        if ($uri->path instanceof Path) {
            $target = (string) $uri->path;
        }

        if ($uri->query instanceof Query) {
            $target .= '?'.$uri->query;
        }

        $this->target = $target;
    }

    public function withMethod(HttpMethod $method): self
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function withUri(Uri $uri): self
    {
        $new = clone $this;
        $new->uri = $uri;

        $new->setTarget($uri);

        return $new;
    }

    public function withTarget(string $target): self
    {
        $new = clone $this;
        $new->target = $target;

        return $new;
    }
}
