<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Infra\Http\UriFactory;

final class UriTest extends TestCase
{
    public function test_it_should_return_authority(): void
    {
        $uri = new UriFactory()->createFromString('https://user:pass@example.com:8080');
        $uriWithoutUserInfo = new UriFactory()->createFromString('https://example.com:8080');

        $this->assertEquals('user:pass@example.com:8080', $uri->getAuthority());
        $this->assertEquals('example.com:8080', $uriWithoutUserInfo->getAuthority());
    }
}
