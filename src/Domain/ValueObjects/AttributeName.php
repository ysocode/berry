<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class AttributeName implements Stringable
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
        if ($value === '') {
            return new Error('Attribute name cannot be empty.');
        }

        $pattern = '/^[a-zA-Z][a-zA-Z0-9-]*$/';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Attribute name contains invalid characters.');
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
