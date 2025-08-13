<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class DirPath implements Stringable
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
        if (! is_dir($value)) {
            return new Error('Dir path must point to an existing directory.');
        }

        if (str_ends_with($value, '/')) {
            return new Error('Dir path must not end with a slash.');
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
