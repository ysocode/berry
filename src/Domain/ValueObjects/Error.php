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
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    private function validate(string $value): true|Error
    {
        $length = mb_strlen($value);
        if ($length < 3 || $length > 255) {
            return new Error('Error must be between 3 and 255 characters.');
        }

        return true;
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
