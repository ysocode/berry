<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use NoDiscard;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Types\RequestTarget;
use YSOCode\Berry\Domain\Types\UriPath;
use YSOCode\Berry\Domain\Types\UriQuery;

trait RequestTrait
{
    public private(set) HttpMethod $method;

    public private(set) Uri $uri;

    public private(set) RequestTarget $target;

    private function setTarget(Uri $uri): void
    {
        $target = '/';

        if ($uri->path instanceof UriPath) {
            $target = (string) $uri->path;
        }

        if ($uri->query instanceof UriQuery) {
            $target .= '?'.$uri->query;
        }

        $this->target = new RequestTarget($target);
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withMethod(HttpMethod $method): self
    {
        return clone ($this, ['method' => $method]);
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withUri(string $uri): self
    {
        $uri = new UriFactory()->createFromString($uri);

        return clone ($this, ['uri' => $uri]);
    }

    #[NoDiscard('HTTP messages are immutable; use the returned clone.')]
    public function withTarget(string $target): self
    {
        $target = new RequestTarget($target);

        return clone ($this, ['target' => $target]);
    }
}
