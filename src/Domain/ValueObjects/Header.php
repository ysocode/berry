<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Header
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
        $validation = self::validate($name, $value);
        if ($validation instanceof Error) {
            throw new InvalidArgumentException((string) $validation);
        }

        $this->value = $value;
    }

    /**
     * @param  array<string>  $value
     */
    public static function isValid(HeaderName $name, array $value): bool
    {
        return self::validate($name, $value) === true;
    }

    /**
     * @param  array<string>  $value
     */
    private static function validate(HeaderName $name, array $value): true|Error
    {
        if ($value === []) {
            return new Error(sprintf('Header value cannot be empty for "%s".', $name));
        }

        foreach ($value as $v) {
            if (! is_string($v)) {
                return new Error(sprintf('Each value for header "%s" must be a string.', $name));
            }

            $pattern = '/[\r\n]/';
            if (preg_match($pattern, $v)) {
                return new Error(sprintf('Header value for "%s" contains invalid characters (\\r or \\n).', $name));
            }

            $pattern = '/^[\x20-\x7E]*$/';
            if (in_array(preg_match($pattern, $v), [0, false], true)) {
                return new Error(sprintf('Header value for "%s" must contain only visible ASCII characters.', $name));
            }
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return strcasecmp((string) $this->name, (string) $other->name) === 0
            && $this->value === $other->value;
    }
}
