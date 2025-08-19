<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

enum Scheme: string
{
    case HTTP = 'http';
    case HTTPS = 'https';

    public function getDefaultPort(): int
    {
        return match ($this) {
            self::HTTP => 80,
            self::HTTPS => 443,
        };
    }
}
