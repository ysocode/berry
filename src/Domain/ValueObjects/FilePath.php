<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class FilePath implements Stringable
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

        $pattern = '#^/(?:[a-zA-Z0-9_\-.]+/?)*$#';
        if (preg_match($pattern, $value) !== 1) {
            return new Error('Invalid path format.');
        }

        if (file_exists($value) && ! is_file($value)) {
            return new Error('Path must point to a file.');
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
