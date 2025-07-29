<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Name;

final class NameTest extends TestCase
{
    public function test_it_should_accept_valid_names(): void
    {
        $validNames = [
            'home',
            'user.profile',
            'dashboard.index',
            'v1.api.users',
            'route1',
            'r1.v2.home',
            '123start.valid',
            'a.b.c.d',
        ];

        foreach ($validNames as $value) {
            $this->assertTrue(
                Name::isValid($value),
                sprintf('Expected "%s" to be valid.', $value)
            );

            $name = new Name($value);

            $this->assertSame($value, (string) $name);
        }
    }

    public function test_it_should_reject_invalid_names(): void
    {
        $invalidNames = [
            '',
            'ab',
            '.start',
            'a#bc',
            'abc-',
            'a bc',
            '...',
            '.a.b',
            str_repeat('a', 256),
        ];

        foreach ($invalidNames as $value) {
            $this->assertFalse(Name::isValid($value), "Expected '$value' to be invalid.");
            $this->expectException(InvalidArgumentException::class);

            new Name($value);
        }
    }

    public function test_it_should_check_equality_between_name_objects(): void
    {
        $name1 = new Name('routes.user.list');
        $name2 = new Name('routes.user.list');
        $name3 = new Name('routes.user.edit');

        $this->assertTrue($name1->equals($name2));
        $this->assertFalse($name1->equals($name3));
    }

    public function test_it_should_return_value_as_string(): void
    {
        $name = new Name('admin.dashboard');

        $this->assertSame('admin.dashboard', (string) $name);
    }

    public function test_it_should_check_validity_statistically(): void
    {
        $this->assertTrue(Name::isValid('products.list'));
        $this->assertFalse(Name::isValid('.invalid.start'));
    }
}
