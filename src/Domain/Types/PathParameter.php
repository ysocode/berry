<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Types;

final readonly class PathParameter
{
    public function __construct(
        public PathParameterName $name,
        public string $value
    ) {}
}
