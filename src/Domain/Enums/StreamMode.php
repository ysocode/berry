<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Enums;

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
    case CREATE = 'c';
    case CREATE_READ = 'c+';

    case READ_BINARY = 'rb';
    case READ_WRITE_BINARY = 'r+b';
    case WRITE_BINARY = 'wb';
    case WRITE_READ_BINARY = 'w+b';
    case APPEND_BINARY = 'ab';
    case APPEND_READ_BINARY = 'a+b';
    case EXCLUSIVE_CREATE_BINARY = 'xb';
    case EXCLUSIVE_CREATE_READ_BINARY = 'x+b';
    case CREATE_BINARY = 'cb';
    case CREATE_READ_BINARY = 'c+b';

    public function isReadable(): bool
    {
        return match ($this) {
            self::READ,
            self::READ_WRITE,
            self::WRITE_READ,
            self::APPEND_READ,
            self::EXCLUSIVE_CREATE_READ,
            self::CREATE_READ,
            self::READ_BINARY,
            self::READ_WRITE_BINARY,
            self::WRITE_READ_BINARY,
            self::APPEND_READ_BINARY,
            self::EXCLUSIVE_CREATE_READ_BINARY,
            self::CREATE_READ_BINARY => true,
            default => false,
        };
    }

    public function isWritable(): bool
    {
        return match ($this) {
            self::READ_WRITE,
            self::WRITE,
            self::WRITE_READ,
            self::APPEND,
            self::APPEND_READ,
            self::EXCLUSIVE_CREATE,
            self::EXCLUSIVE_CREATE_READ,
            self::CREATE,
            self::CREATE_READ,
            self::READ_WRITE_BINARY,
            self::WRITE_BINARY,
            self::WRITE_READ_BINARY,
            self::APPEND_BINARY,
            self::APPEND_READ_BINARY,
            self::EXCLUSIVE_CREATE_BINARY,
            self::EXCLUSIVE_CREATE_READ_BINARY,
            self::CREATE_BINARY,
            self::CREATE_READ_BINARY => true,
            default => false,
        };
    }
}
