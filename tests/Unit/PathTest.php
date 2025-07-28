<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Path;

final class PathTest extends TestCase
{
    public function test_it_should_accept_valid_paths(): void
    {
        $validPaths = [
            '/',
            '/users',
            '/users/{id}',
            '/users/profile-picture',
            '/blog/posts/{slug}',
            '/api/v1/resource_123',
        ];

        foreach ($validPaths as $valid) {
            $path = new Path($valid);
            $this->assertSame($valid, (string) $path);
        }
    }

    public function test_it_should_throw_exception_for_invalid_paths(): void
    {
        $invalidPaths = [
            '',
            'users',
            '//double',
            '/?',
            '/user name',
            '/user#1',
            '/user$',
            '/user//id',
        ];

        foreach ($invalidPaths as $invalid) {
            $this->expectException(InvalidArgumentException::class);
            new Path($invalid);
        }
    }

    public function test_it_should_compare_two_paths_correctly(): void
    {
        $a = new Path('/users');
        $b = new Path('/users');
        $c = new Path('/posts');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_it_should_return_string_representation(): void
    {
        $path = new Path('/hello');
        $this->assertSame('/hello', (string) $path);
    }

    public function test_it_should_validate_paths_using_static_method(): void
    {
        $this->assertTrue(Path::isValid('/abc-123'));
        $this->assertFalse(Path::isValid('no-slash'));
    }
}
