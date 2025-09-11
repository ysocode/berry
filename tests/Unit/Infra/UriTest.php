<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;
use YSOCode\Berry\Infra\Http\Uri;

final class UriTest extends TestCase
{
    private function createUri(): Uri
    {
        return new Uri(
            Scheme::HTTPS,
            new Host('example.com'),
            new Port(8080),
            null,
            new UserInfo('ysocode', 'berry')
        );
    }

    public function test_it_should_create_a_valid_uri(): void
    {
        $uri = $this->createUri();

        $this->assertEquals('https://ysocode:berry@example.com:8080', (string) $uri);
        $this->assertEquals(Scheme::HTTPS, $uri->scheme);
        $this->assertEquals(new Host('example.com'), $uri->host);
        $this->assertEquals(new Port(8080), $uri->port);
        $this->assertNull($uri->path);
        $this->assertEquals(new UserInfo('ysocode', 'berry'), $uri->userInfo);
        $this->assertNull($uri->query);
        $this->assertNull($uri->fragment);
    }

    public function test_it_should_return_authority(): void
    {
        $uri = $this->createUri();
        $uriWithoutUserInfo = new Uri(
            Scheme::HTTPS,
            new Host('example.com'),
            new Port(8080),
        );

        $this->assertEquals('ysocode:berry@example.com:8080', $uri->getAuthority());
        $this->assertEquals('example.com:8080', $uriWithoutUserInfo->getAuthority());
    }

    public function test_it_should_return_cloned_uri_with_changed_properties(): void
    {
        $uri = $this->createUri();

        $newUri = $uri->withScheme(Scheme::HTTP);
        $this->assertEquals('http://ysocode:berry@example.com:8080', (string) $newUri);

        $newUri = $newUri->withHost(new Host('example.org'));
        $this->assertEquals('http://ysocode:berry@example.org:8080', (string) $newUri);

        $newUri = $newUri->withPort(new Port(1234));
        $this->assertEquals('http://ysocode:berry@example.org:1234', (string) $newUri);

        $newUri = $newUri->withPath(new Path('/newpath'));
        $this->assertEquals('http://ysocode:berry@example.org:1234/newpath', (string) $newUri);

        $newUri = $newUri->withUserInfo(new UserInfo('newuser', 'newpass'));
        $this->assertEquals('http://newuser:newpass@example.org:1234/newpath', (string) $newUri);

        $newUri = $newUri->withQuery(new Query('newquery=2'));
        $this->assertEquals('http://newuser:newpass@example.org:1234/newpath?newquery=2', (string) $newUri);

        $newUri = $newUri->withFragment(new Fragment('newfrag'));
        $this->assertEquals('http://newuser:newpass@example.org:1234/newpath?newquery=2#newfrag', (string) $newUri);
    }

    public function test_it_should_use_default_port_from_scheme_when_port_is_not_provided(): void
    {
        $httpsUri = new Uri(
            Scheme::HTTPS,
            new Host('example.com'),
        );
        $this->assertEquals(new Port(443), $httpsUri->port);

        $httpUri = new Uri(
            Scheme::HTTP,
            new Host('example.com'),
        );
        $this->assertEquals(new Port(80), $httpUri->port);
    }
}
