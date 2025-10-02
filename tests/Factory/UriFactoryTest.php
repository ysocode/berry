<?php

declare(strict_types=1);

namespace Tests\Factory;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\Enums\UriScheme;
use YSOCode\Berry\Infra\Http\UriFactory;

final class UriFactoryTest extends TestCase
{
    public function test_it_should_create_a_uri_from_string(): void
    {
        $uri = new UriFactory()->createFromString('https://user:pass@example.com:8080/path/to/resource?query=param#fragment');

        $this->assertEquals(UriScheme::HTTPS, $uri->scheme);
        $this->assertEquals('example.com', (string) $uri->host);
        $this->assertEquals(8080, $uri->port->value);
        $this->assertEquals('/path/to/resource', (string) $uri->path);
        $this->assertEquals('query=param', (string) $uri->query);
        $this->assertEquals('fragment', (string) $uri->fragment);
    }

    public function test_it_should_create_a_uri_from_globals(): void
    {
        $headers = [
            'HTTP_HOST' => 'ysocode.com:8080',
        ];

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => 'https',
            'SERVER_NAME' => 'ysocode.com',
            'SERVER_PORT' => 8080,
            'REQUEST_URI' => '/path/to/resource?query=param',
            'QUERY_STRING' => 'query=param',
            ...$headers,
        ];

        $uri = new UriFactory()->createFromGlobals();

        $this->assertEquals(UriScheme::HTTPS, $uri->scheme);
        $this->assertEquals('ysocode.com', (string) $uri->host);
        $this->assertEquals(8080, $uri->port->value);
        $this->assertEquals('/path/to/resource', (string) $uri->path);
        $this->assertEquals('query=param', (string) $uri->query);
        $this->assertNull($uri->fragment);
    }
}
