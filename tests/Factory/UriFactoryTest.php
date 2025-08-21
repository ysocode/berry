<?php

declare(strict_types=1);

namespace Tests\Factory;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Infra\Http\UriFactory;

final class UriFactoryTest extends TestCase
{
    public function test_it_should_create_a_uri_from_string(): void
    {
        $uri = new UriFactory()->createFromString('https://user:pass@example.com:8080/path/to/resource?query=param#fragment');

        $this->assertEquals(Scheme::HTTPS, $uri->scheme);
        $this->assertEquals(new Host('example.com'), $uri->host);
        $this->assertEquals(new Port(8080), $uri->port);
        $this->assertEquals(new Path('/path/to/resource'), $uri->path);
        $this->assertEquals(new Query('query=param'), $uri->query);
        $this->assertEquals(new Fragment('fragment'), $uri->fragment);
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

        $this->assertEquals(Scheme::HTTPS, $uri->scheme);
        $this->assertEquals(new Host('ysocode.com'), $uri->host);
        $this->assertEquals(new Port(8080), $uri->port);
        $this->assertEquals(new Path('/path/to/resource'), $uri->path);
        $this->assertEquals(new Query('query=param'), $uri->query);
        $this->assertNull($uri->fragment);
    }
}
