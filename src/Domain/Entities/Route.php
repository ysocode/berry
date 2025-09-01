<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

class Route
{
    /**
     * @param  RequestHandlerInterface|Closure(ServerRequest $request): Response  $handler
     */
    public function __construct(
        public HttpMethod $method,
        public Path $path,
        public RequestHandlerInterface|Closure $handler,
        private(set) ?Name $name = null
    ) {}

    public function setName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }
}
