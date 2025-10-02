<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Enums;

enum StreamSeekWhence: int
{
    case SET = 0;
    case CUR = 1;
    case END = 2;
}
