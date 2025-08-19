<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use RuntimeException;
use Stringable;

final readonly class Header implements Stringable
{
    /**
     * @var array<string>
     */
    public array $values;

    /**
     * @param  array<string>  $values
     */
    public function __construct(
        public HeaderName $name,
        array $values
    ) {
        $isValid = self::validate($values);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->values = $values;
    }

    /**
     * @param  array<string>  $values
     */
    public static function isValid(array $values): bool
    {
        return self::validate($values) === true;
    }

    /**
     * @param  array<string>  $values
     */
    private static function validate(array $values): true|Error
    {
        if ($values === []) {
            return new Error('Header must have at least one value.');
        }

        foreach ($values as $index => $value) {
            if (! is_string($value)) {
                return new Error('Header value must be string.');
            }

            if ($value === '') {
                return new Error("Header value at index {$index} cannot be empty.");
            }

            $pattern = '/[\0\r\n]/';
            if (preg_match($pattern, $value) === 1) {
                return new Error('Header value contains invalid characters.');
            }
        }

        return true;
    }

    public function __toString(): string
    {
        $lowerHeaderName = strtolower((string) $this->name);
        if ($lowerHeaderName === 'set-cookie' && count($this->values) > 1) {
            throw new RuntimeException('Set-Cookie cannot be concatenated.');
        }

        return $this->name.': '.implode(', ', $this->values);
    }
}
