<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Query;

trait RequestTrait
{
    public private(set) HttpMethod $method;

    public private(set) Uri $uri;

    public private(set) string $target;

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
