<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class FileName implements Stringable
{
    public string $value;

    private const array RESERVED_NAMES = ['.', '..'];

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
        $length = strlen($value);
        if (! self::between($length, 1, 255)) {
            return new Error('File name must be between 1 and 255 characters.');
        }

        if (in_array($value, self::RESERVED_NAMES, true)) {
            return new Error('File name cannot be a reserved name.');
        }

        $pattern = '/[\/?%*:|"<>\x00]/';
        if (preg_match($pattern, $value) === 1) {
            return new Error('File name contains invalid characters.');
        }

        return true;
    }

    private static function between(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
