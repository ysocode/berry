<?php

declare(strict_types=1);

namespace Tests\Factory\Infra;

use PHPUnit\Framework\TestCase;
use Tests\Traits\ServerEnvironmentSetupTrait;
use YSOCode\Berry\Domain\Enums\UriScheme;
use YSOCode\Berry\Infra\Http\UriFactory;

final class UriFactoryTest extends TestCase
{
    use ServerEnvironmentSetupTrait;

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
        $uri = new UriFactory()->createFromGlobals();

        $this->assertEquals(UriScheme::HTTPS, $uri->scheme);
        $this->assertEquals('ysocode.com', (string) $uri->host);
        $this->assertEquals(443, $uri->port->value);
        $this->assertEquals('/', (string) $uri->path);
        $this->assertEquals('query=param', (string) $uri->query);
        $this->assertNull($uri->fragment);
    }

    public function test_it_should_create_a_uri_from_globals_when_http_host_contains_port(): void
    {
        $_SERVER['HTTP_HOST'] = 'ysocode.com:8443';

        $uri = new UriFactory()->createFromGlobals();

        $this->assertEquals('ysocode.com', (string) $uri->host);
        $this->assertEquals(8443, $uri->port->value);
    }

    public function test_it_should_create_a_uri_from_globals_when_http_host_does_not_contain_port_but_server_port_does(): void
    {
        $_SERVER['HTTP_HOST'] = 'ysocode.com';
        $_SERVER['SERVER_PORT'] = 8443;

        $uri = new UriFactory()->createFromGlobals();

        $this->assertEquals('ysocode.com', (string) $uri->host);
        $this->assertEquals(8443, $uri->port->value);
    }
}
