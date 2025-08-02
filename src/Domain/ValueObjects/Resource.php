<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Resource
{
    public mixed $value;

    public function __construct(mixed $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    public static function isValid(mixed $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(mixed $value): true|Error
    {
        if (! is_resource($value)) {
            return new Error('The given value is not a valid resource.');
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
