<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\Request;
use YSOCode\Berry\Infra\Http\Response;

final readonly class Route
{
    /**
     * @param  Handler|Closure(Request): Response  $handler
     */
    public function __construct(
        public Method $method,
        public Path $path,
        public Handler|Closure $handler,
        public ?Name $name = null,
    ) {}
}
