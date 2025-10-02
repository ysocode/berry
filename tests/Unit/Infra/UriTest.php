<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\Enums\UriScheme;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\UriFragment;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Domain\ValueObjects\UriQuery;
use YSOCode\Berry\Domain\ValueObjects\UriUserInfo;
use YSOCode\Berry\Infra\Http\Uri;

final class UriTest extends TestCase
{
    private function createUri(): Uri
    {
        return new Uri(
            UriScheme::HTTPS,
            new Host('example.com'),
            new Port(8080),
            null,
            new UriUserInfo('ysocode', 'berry')
        );
    }

    public function test_it_should_create_a_valid_uri(): void
    {
        $uri = $this->createUri();

        $this->assertEquals('https://ysocode:berry@example.com:8080', (string) $uri);
        $this->assertEquals(UriScheme::HTTPS, $uri->scheme);
        $this->assertEquals('example.com', (string) $uri->host);
        $this->assertEquals(8080, $uri->port->value);
        $this->assertNull($uri->path);
        $this->assertEquals('ysocode:berry', (string) $uri->userInfo);
        $this->assertNull($uri->query);
        $this->assertNull($uri->fragment);
    }

    public function test_it_should_return_authority(): void
    {
        $uri = $this->createUri();
        $uriWithoutUserInfo = new Uri(
            UriScheme::HTTPS,
            new Host('example.com'),
            new Port(8080),
        );

        $this->assertEquals('ysocode:berry@example.com:8080', $uri->getAuthority());
        $this->assertEquals('example.com:8080', $uriWithoutUserInfo->getAuthority());
    }

    public function test_it_should_return_cloned_uri_with_changed_properties(): void
    {
        $uri = $this->createUri();

        $newUri = $uri->withScheme(UriScheme::HTTP);
        $this->assertEquals('http://ysocode:berry@example.com:8080', (string) $newUri);

        $newUri = $newUri->withHost(new Host('example.org'));
        $this->assertEquals('http://ysocode:berry@example.org:8080', (string) $newUri);

        $newUri = $newUri->withPort(new Port(1234));
        $this->assertEquals('http://ysocode:berry@example.org:1234', (string) $newUri);

        $newUri = $newUri->withPath(new UriPath('/newpath'));
        $this->assertEquals('http://ysocode:berry@example.org:1234/newpath', (string) $newUri);

        $newUri = $newUri->withUserInfo(new UriUserInfo('newuser', 'newpass'));
        $this->assertEquals('http://newuser:newpass@example.org:1234/newpath', (string) $newUri);

        $newUri = $newUri->withQuery(new UriQuery('newquery=2'));
        $this->assertEquals('http://newuser:newpass@example.org:1234/newpath?newquery=2', (string) $newUri);

        $newUri = $newUri->withFragment(new UriFragment('newfrag'));
        $this->assertEquals('http://newuser:newpass@example.org:1234/newpath?newquery=2#newfrag', (string) $newUri);
    }

    public function test_it_should_use_default_port_from_scheme_when_port_is_not_provided(): void
    {
        $httpsUri = new Uri(
            UriScheme::HTTPS,
            new Host('example.com'),
        );
        $this->assertEquals(443, $httpsUri->port->value);

        $httpUri = new Uri(
            UriScheme::HTTP,
            new Host('example.com'),
        );
        $this->assertEquals(80, $httpUri->port->value);
    }
}
