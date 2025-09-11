<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\RequestTarget;

final class RequestTargetTest extends TestCase
{
    public function test_it_should_reject_empty_target(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Request target cannot be empty.');

        new RequestTarget('');
    }

    public function test_it_should_reject_generic_invalid_form(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Request target does not match any valid form.');

        new RequestTarget(' ;klj asdf');
    }

    public function test_it_should_accept_asterisk_form(): void
    {
        $target = new RequestTarget('*');

        $this->assertSame('*', (string) $target);
    }

    public function test_it_should_accept_valid_absolute_form(): void
    {
        $targetWithQuery = new RequestTarget('http://example.com/path?query=1');
        $targetWithoutQuery = new RequestTarget('https://example.com');

        $this->assertSame('http://example.com/path?query=1', (string) $targetWithQuery);
        $this->assertSame('https://example.com', (string) $targetWithoutQuery);
    }

    public function test_it_should_reject_invalid_absolute_form(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid absolute-form request target.');

        new RequestTarget('https://');
    }

    public function test_it_should_accept_valid_origin_form(): void
    {
        $targetWithQuery = new RequestTarget('/foo/bar?query=value');
        $targetWithoutQuery = new RequestTarget('/foo/bar');
        $targetOnlyBar = new RequestTarget('/');

        $this->assertSame('/foo/bar?query=value', (string) $targetWithQuery);
        $this->assertSame('/foo/bar', (string) $targetWithoutQuery);
        $this->assertSame('/', (string) $targetOnlyBar);
    }

    public function test_it_should_reject_invalid_origin_form(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid origin-form request target.');

        new RequestTarget('/foo bar');
    }

    public function test_it_should_accept_valid_authority_form(): void
    {
        $targetWithPort = new RequestTarget('example.com:8080');
        $targetWithoutPort = new RequestTarget('example.com');

        $this->assertSame('example.com:8080', (string) $targetWithPort);
        $this->assertSame('example.com', (string) $targetWithoutPort);
    }

    public function test_it_should_reject_invalid_authority_form(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid authority-form request target.');

        new RequestTarget('example.com:port');
    }
}
