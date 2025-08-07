<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class StreamResource
{
    /**
     * @var resource
     */
    public mixed $value;

    /**
     * @param  resource  $value
     */
    public function __construct(mixed $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    /**
     * @param  resource  $value
     */
    public static function isValid(mixed $value): bool
    {
        return self::validate($value) === true;
    }

    /**
     * @param  resource  $value
     */
    private static function validate(mixed $value): true|Error
    {
        if (! is_resource($value)) {
            return new Error('Resource must be a valid stream resource.');
        }

        return true;
    }
}
