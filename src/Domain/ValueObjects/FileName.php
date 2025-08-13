<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class FileName implements Stringable
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
            return new Error('File name cannot be empty.');
        }

        $pattern = '/[\/?%*:|"<>\x00]/';
        if (preg_match($pattern, $value) === 1) {
            return new Error('File name contains invalid characters.');
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
