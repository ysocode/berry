<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use InvalidArgumentException;
use Stringable;

final readonly class Name implements Stringable
{
    public string $value;

    public function __construct(
        string $value
    ) {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(string $value): true|Error
    {
        $length = mb_strlen($value);
        if ($length < 3 || $length > 255) {
            return new Error('Name must be between 3 and 255 characters.');
        }

        $pattern = '/^[A-Za-z0-9](?:[A-Za-z0-9.]*[A-Za-z0-9])?$/';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Name must start with a letter and contain only letters, numbers, and dots.');
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
