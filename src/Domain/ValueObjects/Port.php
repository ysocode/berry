<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class Port implements Stringable
{
    public int $value;

    public function __construct(int $value)
    {
        $validation = self::validate($value);
        if ($validation instanceof Error) {
            throw new InvalidArgumentException((string) $validation);
        }

        $this->value = $value;
    }

    public static function isValid(int $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(int $value): true|Error
    {
        if ($value < 1 || $value > 65535) {
            return new Error("Invalid port: {$value}. Valid range is 1 to 65535.");
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
