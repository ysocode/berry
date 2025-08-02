<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;

enum Scheme: string
{
    case HTTPS = 'https';
    case HTTP = 'http';

    public function defaultPort(): int
    {
        return match ($this) {
            self::HTTP => 80,
            self::HTTPS => 443,
        };
    }

    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Unsupported scheme: {$value}.");
    }
}
