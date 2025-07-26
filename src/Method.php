<?php

declare(strict_types=1);

namespace YSOCode\Berry;

enum Method: string
{
    case GET = 'GET';
    case PUT = 'PUT';
    case POST = 'POST';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
}
