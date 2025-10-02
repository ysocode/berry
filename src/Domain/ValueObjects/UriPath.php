<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class UriPath implements Stringable
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
        if (! str_starts_with($value, '/')) {
            return new Error('Uri path must start with "/".');
        }

        $pattern = '/^(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\/]|%[0-9A-Fa-f]{2})*$/';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Uri path contains invalid characters.');
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function prepend(self $other): self
    {
        $otherValue = rtrim($other->value, '/');
        $current = '/'.ltrim($this->value, '/');

        return new self($otherValue.$current);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
