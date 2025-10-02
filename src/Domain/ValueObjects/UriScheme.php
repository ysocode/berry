<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

enum UriScheme: string
{
    case HTTP = 'http';
    case HTTPS = 'https';

    public function getDefaultPort(): Port
    {
        return match ($this) {
            self::HTTP => new Port(80),
            self::HTTPS => new Port(443),
        };
    }
}
