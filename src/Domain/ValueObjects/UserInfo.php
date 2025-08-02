<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class UserInfo implements Stringable
{
    public string $user;

    public ?string $password;

    public function __construct(string $user, ?string $password = null)
    {
        $validation = self::validate($user, $password);
        if ($validation instanceof Error) {
            throw new InvalidArgumentException((string) $validation);
        }

        $this->user = $user;
        $this->password = $password;
    }

    public static function isValid(string $user, ?string $password = null): bool
    {
        return self::validate($user, $password) === true;
    }

    private static function validate(string $user, ?string $password = null): true|Error
    {
        if ($user === '' || $user === '0') {
            return new Error('User cannot be empty.');
        }

        $pattern = '/^[\w\-.~]+$/';
        if (in_array(preg_match($pattern, $user), [0, false], true)) {
            return new Error('User contains invalid characters.');
        }

        if ($password !== null) {
            $pattern = '/^[\w\-.~!@#$%^&*()]+$/';
            if (in_array(preg_match($pattern, $password), [0, false], true)) {
                return new Error('Password contains invalid characters.');
            }
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->user === $other->user && $this->password === $other->password;
    }

    public function __toString(): string
    {
        return $this->password !== null
            ? "{$this->user}:{$this->password}"
            : $this->user;
    }
}
