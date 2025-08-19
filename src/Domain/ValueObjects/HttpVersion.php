<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class HttpVersion implements Stringable
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
        if (! in_array($value, ['1.0', '1.1', '2.0'], true)) {
            return new Error(sprintf('HTTP version "%s" is not supported.', $value));
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
