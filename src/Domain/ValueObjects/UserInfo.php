<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class UserInfo implements Stringable
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
        if (substr_count($value, ':') > 1) {
            return new Error('UserInfo must contain at most one colon ":" separating user and password.');
        }

        [$user, $pass] = array_pad(explode(':', $value, 2), 2, null);

        if (! self::validatePart($user)) {
            return new Error('UserInfo user contains invalid characters.');
        }

        if (! self::validatePart($pass)) {
            return new Error('UserInfo password contains invalid characters.');
        }

        return true;
    }

    private static function validatePart(?string $part): bool
    {
        if (is_null($part)) {
            return true;
        }

        $pattern = '/^(?:[A-Za-z0-9\-._~!$&\'()*+,;=]|%[0-9A-Fa-f]{2})*$/';

        return preg_match($pattern, $part) === 1;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
