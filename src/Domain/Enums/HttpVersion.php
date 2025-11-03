<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Enums;

enum HttpVersion: string
{
    case V1_0 = '1.0';
    case V1_1 = '1.1';
    case V2_0 = '2.0';
}
