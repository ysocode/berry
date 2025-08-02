<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

enum StreamMode: string
{
    case READ = 'r';
    case READ_WRITE = 'r+';
    case WRITE = 'w';
    case WRITE_READ = 'w+';
    case APPEND = 'a';
    case APPEND_READ = 'a+';
    case EXCLUSIVE_CREATE = 'x';
    case EXCLUSIVE_CREATE_READ = 'x+';
    case OPEN_READ_WRITE = 'c';
    case OPEN_READ_WRITE_CREATE = 'c+';

    case READ_WRITE_BINARY = 'r+b';
    case WRITE_READ_BINARY = 'w+b';
    case APPEND_READ_BINARY = 'a+b';
    case EXCLUSIVE_CREATE_READ_BINARY = 'x+b';
    case OPEN_READ_WRITE_CREATE_BINARY = 'c+b';

    case READ_WRITE_TEXT = 'r+t';
    case WRITE_READ_TEXT = 'w+t';

    public function isReadable(): bool
    {
        return str_contains($this->value, 'r') || str_contains($this->value, '+');
    }

    public function isWritable(): bool
    {
        return str_contains($this->value, 'w')
            || str_contains($this->value, 'a')
            || str_contains($this->value, 'x')
            || str_contains($this->value, 'c')
            || str_contains($this->value, '+');
    }
}
