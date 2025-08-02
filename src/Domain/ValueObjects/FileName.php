<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class FileName implements Stringable
{
    public string $value;

    public function __construct(
        string $value
    ) {
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
        $length = mb_strlen($value);
        if ($length < 3 || $length > 255) {
            return new Error('File name must be between 3 and 255 characters.');
        }

        if (preg_match('/[\/\\\\:*?"<>|]/', $value)) {
            return new Error('File name contains invalid characters.');
        }

        if (preg_match('/[. ]$/', $value)) {
            return new Error('File name cannot end with a space or dot.');
        }

        if (preg_match('/^\.[^.]+$/', $value)) {
            return new Error('File name cannot be just an extension.');
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
