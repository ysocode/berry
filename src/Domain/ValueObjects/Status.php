<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

enum Status: int
{
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;

    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;

    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;

    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case SERVICE_UNAVAILABLE = 503;
}
