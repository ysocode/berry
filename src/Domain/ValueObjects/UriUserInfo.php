<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class UriUserInfo implements Stringable
{
    public string $user;

    public ?string $password;

    public function __construct(string $user, ?string $password)
    {
        $isValid = self::validate($user, $password);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->user = $user;
        $this->password = $password;
    }

    public static function isValid(string $user, ?string $password): bool
    {
        return self::validate($user, $password) === true;
    }

    private static function validate(string $user, ?string $password): true|Error
    {
        if ($user === '') {
            return new Error('User cannot be empty.');
        }

        if (! self::validatePart($user)) {
            return new Error('User contains invalid characters.');
        }

        if (is_string($password)) {
            if ($password === '') {
                return new Error('Password cannot be empty.');
            }

            if (! self::validatePart($password)) {
                return new Error('Password contains invalid characters.');
            }
        }

        return true;
    }

    private static function validatePart(?string $part): bool
    {
        $pattern = '/^(?:[A-Za-z0-9\-._~!$&\'()*+,;=]|%[0-9A-Fa-f]{2})*$/';

        return preg_match($pattern, (string) $part) === 1;
    }

    public function __toString(): string
    {
        if (is_string($this->password)) {
            return "{$this->user}:{$this->password}";
        }

        return $this->user;
    }
}
