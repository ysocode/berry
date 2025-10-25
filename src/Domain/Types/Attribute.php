<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Types;

final readonly class Attribute
{
    public function __construct(
        public AttributeName $name,
        public mixed $value
    ) {}
}
