<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RouteName;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;

final readonly class Route
{
    public function __construct(
        public HttpMethod $method,
        public RoutePathPattern $pathPattern,
        public RequestHandler $handler,
        public ?RouteName $name = null
    ) {}
}
