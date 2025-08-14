<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class Header implements Stringable
{
    /**
     * @var array<string>
     */
    public array $value;

    /**
     * @param  array<string>  $value
     */
    public function __construct(
        public HeaderName $name,
        array $value
    ) {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    /**
     * @param  array<string>  $value
     */
    public static function isValid(array $value): bool
    {
        return self::validate($value) === true;
    }

    /**
     * @param  array<string>  $value
     */
    private static function validate(array $value): true|Error
    {
        if ($value === []) {
            return new Error('Header must have at least one value.');
        }

        foreach ($value as $v) {
            if (! is_string($v)) {
                return new Error('Header values must be strings.');
            }

            $pattern = '/[\0\r\n]/';
            if (preg_match($pattern, $v) === 1) {
                return new Error('Header value contains invalid characters.');
            }
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->name.': '.implode(', ', $this->value);
    }
}
