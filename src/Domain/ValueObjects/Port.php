<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use Error;
use InvalidArgumentException;

final readonly class Port
{
    public int $value;

    public function __construct(int $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    public static function isValid(int $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(int $value): true|Error
    {
        if (! self::between($value, 1, 65535)) {
            return new Error('Port must be between 1 and 65535.');
        }

        return true;
    }

    private static function between(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
