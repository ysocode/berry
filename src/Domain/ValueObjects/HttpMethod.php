<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

enum HttpMethod: string
{
    case GET = 'GET';
    case PUT = 'PUT';
    case POST = 'POST';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';

    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn (self $method) => $method->value, self::cases());
    }
}
