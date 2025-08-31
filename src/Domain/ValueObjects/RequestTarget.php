<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class RequestTarget implements Stringable
{
    public string $value;

    const string ASTERISK_FORM = '*';

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
            return new Error('Request target cannot be empty.');
        }

        if ($value === self::ASTERISK_FORM) {
            return true;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return self::validateAbsoluteForm($value);
        }

        if (str_starts_with($value, '/')) {
            return self::validateOriginForm($value);
        }

        if (self::isAuthorityFormCandidate($value)) {
            return self::validateAuthorityForm($value);
        }

        return new Error('Request target does not match any valid form.');
    }

    public static function validateAbsoluteForm(string $value): true|Error
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return new Error('Invalid absolute-form request target.');
        }

        return true;
    }

    public static function validateOriginForm(string $value): true|Error
    {
        $pattern = '/^(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\/?]|%[0-9A-Fa-f]{2})*$/u';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Invalid origin-form request target.');
        }

        return true;
    }

    public static function validateAuthorityForm(string $value): true|Error
    {
        if (in_array(preg_match('/^[A-Za-z0-9.\-]+(?::\d+)?$/', $value), [0, false], true)) {
            return new Error('Invalid authority-form request target.');
        }

        return true;
    }

    public static function isAuthorityFormCandidate(string $value): bool
    {
        $parts = explode('@', $value, 2);
        $hostPart = $parts[array_key_last($parts)];

        [$host] = explode(':', $hostPart, 2);

        return Host::isValid($host);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
