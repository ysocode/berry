<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class Error implements Stringable
{
    public string $value;

    public function __construct(
        string $value
    ) {
        $isValid = $this->validate($value);
        if ($isValid instanceof self) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    private function validate(string $value): true|self
    {
        $length = strlen($value);
        if (! $this->between($length, 3, 255)) {
            return new self('Error must be between 3 and 255 characters.');
        }

        return true;
    }

    private function between(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
