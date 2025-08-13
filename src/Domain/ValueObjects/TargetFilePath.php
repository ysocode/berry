<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\ValueObjects;

use InvalidArgumentException;
use RuntimeException;
use Stringable;

final readonly class TargetFilePath implements Stringable
{
    public DirPath $dirPath;

    public FileName $fileName;

    public function __construct(DirPath $dirPath, FileName $fileName)
    {
        $isValid = self::validate($dirPath, $fileName);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->dirPath = $dirPath;
        $this->fileName = $fileName;
    }

    public static function isValid(DirPath $dirPath, FileName $fileName): bool
    {
        return self::validate($dirPath, $fileName) === true;
    }

    private static function validate(DirPath $dirPath, FileName $fileName): true|Error
    {
        if (! is_writable((string) $dirPath)) {
            throw new RuntimeException('Directory is not writable.');
        }

        $fullPath = $dirPath.'/'.$fileName;
        if (file_exists($fullPath)) {
            return new Error('Target file already exists.');
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->dirPath.'/'.$this->fileName;
    }
}
