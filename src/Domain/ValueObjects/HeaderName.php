<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class HeaderName implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $validation = self::validate($value);
        if ($validation instanceof Error) {
            throw new InvalidArgumentException((string) $validation);
        }

        $this->value = $value;
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(string $value): true|Error
    {
        if ($value === '') {
            return new Error('Header name cannot be empty.');
        }

        $pattern = '/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error("Invalid header name: {$value}.");
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
