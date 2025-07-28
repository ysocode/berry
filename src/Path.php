<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use InvalidArgumentException;
use Stringable;

final readonly class Path implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
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
        if ($value === '' || $value === '0') {
            return new Error('Path cannot be empty.');
        }

        $pattern = '#^/(?:[a-zA-Z0-9_\-{}]+/?)*$#';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Invalid path format.');
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
