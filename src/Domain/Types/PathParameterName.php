<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Types;

use InvalidArgumentException;
use Stringable;

final readonly class PathParameterName implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $sanitizedValue = self::sanitize($value);

        $isValid = self::validate($sanitizedValue);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $sanitizedValue;
    }

    public static function sanitize(string $value): string
    {
        if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(string $value): true|Error
    {
        if ($value === '') {
            return new Error('Path parameter name cannot be empty.');
        }

        $pattern = '/^\w+$/';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Path parameter name contains invalid characters.');
        }

        return true;
    }

    public function getValueWithBraces(): string
    {
        return '{'.$this->value.'}';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
