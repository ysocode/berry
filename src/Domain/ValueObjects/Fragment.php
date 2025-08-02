<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class Fragment implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $validation = self::validate($value);
        if ($validation instanceof Error) {
            throw new InvalidArgumentException((string) $validation);
        }

        $this->value = $value;
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(string $value): true|Error
    {
        $pattern = '/[^\w\-.~!$&\'()*+,;=:@\/?%]/';
        if (preg_match($pattern, $value)) {
            return new Error('Fragment contains invalid characters.');
        }

        $pattern = '/\s/';
        if (preg_match($pattern, $value)) {
            return new Error('Fragment must not contain unencoded whitespace.');
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
