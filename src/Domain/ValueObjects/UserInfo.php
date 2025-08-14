<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class UserInfo implements Stringable
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
        if ($user === '' || $user === '0') {
            return new Error('UserInfo name cannot be empty.');
        }

        if (! self::validatePart($user)) {
            return new Error('UserInfo user contains invalid characters.');
        }

        if (! self::validatePart($password)) {
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
        if (is_string($this->password)) {
            return "{$this->user}:{$this->password}";
        }

        return $this->user;
    }
}
