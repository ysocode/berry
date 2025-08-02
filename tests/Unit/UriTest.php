<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;
use YSOCode\Berry\Infra\Uri;

final class UriTest extends TestCase
{
    public function test_it_should_accept_valid_uri(): void
    {
        $uri = new Uri('https://example.com');

        $this->assertSame('https', $uri->scheme->value);
        $this->assertSame('example.com', (string) $uri->host);
        $this->assertSame('/', (string) $uri->path);
    }

    public function test_it_should_reject_invalid_uris(): void
    {
        $invalidNames = [
            '',
            'http://',
            'ftp://user:pass@',
            '://example.com',
            'http://example.com:port',
            'http://example.com:80/path?query#fragment',
        ];

        foreach ($invalidNames as $value) {
            $this->assertFalse(Uri::isValid($value), "Expected '$value' to be invalid.");
            $this->expectException(InvalidArgumentException::class);

            new Uri($value);
        }
    }

    public function test_it_should_construct_uri_with_all_components(): void
    {
        $uri = new Uri('https://user:pass@example.com:8443/foo/bar?abc=123#section');

        $this->assertSame('https', $uri->scheme->value);
        $this->assertSame('user:pass', (string) $uri->userInfo);
        $this->assertSame('example.com', (string) $uri->host);
        $this->assertSame('8443', (string) $uri->port);
        $this->assertSame('/foo/bar', (string) $uri->path);
        $this->assertSame('abc=123', (string) $uri->query);
        $this->assertSame('section', (string) $uri->fragment);
    }

    public function test_it_should_convert_uri_to_string(): void
    {
        $original = 'https://user:pass@example.com:8080/path/to/resource?x=1#top';
        $uri = new Uri($original);

        $this->assertSame($original, (string) $uri);
    }

    public function test_it_should_return_authority_with_user_info_and_port(): void
    {
        $uri = new Uri('https://user:pass@site.com:8443');

        $this->assertSame('user:pass@site.com:8443', $uri->getAuthority());
    }

    public function test_it_should_return_authority_without_user_info_and_default_port(): void
    {
        $uri = new Uri('http://example.com');

        $this->assertSame('example.com', $uri->getAuthority());
    }

    public function test_it_should_return_new_uri_with_updated_scheme(): void
    {
        $uri = new Uri('http://example.com');
        $newUri = $uri->withScheme(Scheme::HTTPS);

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('http', $uri->scheme->value);
        $this->assertSame('https', $newUri->scheme->value);
    }

    public function test_it_should_return_new_uri_with_updated_user_info(): void
    {
        $uri = new Uri('http://example.com');
        $userInfo = new UserInfo('user', 'pass');
        $newUri = $uri->withUserInfo($userInfo);

        $this->assertNotSame($uri, $newUri);
        $this->assertNull($uri->userInfo);
        $this->assertSame('user:pass', (string) $newUri->userInfo);
    }

    public function test_it_should_return_new_uri_with_updated_host(): void
    {
        $uri = new Uri('http://example.com');
        $host = new Host('newhost.com');
        $newUri = $uri->withHost($host);

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('example.com', (string) $uri->host);
        $this->assertSame('newhost.com', (string) $newUri->host);
    }

    public function test_it_should_return_new_uri_with_updated_port(): void
    {
        $uri = new Uri('http://example.com');
        $port = new Port(8080);
        $newUri = $uri->withPort($port);

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('80', (string) $uri->port);
        $this->assertSame('8080', (string) $newUri->port);
    }

    public function test_it_should_return_new_uri_with_updated_path(): void
    {
        $uri = new Uri('http://example.com');
        $path = new Path('/newpath');
        $newUri = $uri->withPath($path);

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('/', (string) $uri->path);
        $this->assertSame('/newpath', (string) $newUri->path);
    }

    public function test_it_should_return_new_uri_with_updated_query(): void
    {
        $uri = new Uri('http://example.com');
        $query = new Query('a=1&b=2');
        $newUri = $uri->withQuery($query);

        $this->assertNotSame($uri, $newUri);
        $this->assertNull($uri->query);
        $this->assertSame('a=1&b=2', (string) $newUri->query);
    }

    public function test_it_should_return_new_uri_with_updated_fragment(): void
    {
        $uri = new Uri('http://example.com');
        $fragment = new Fragment('section1');
        $newUri = $uri->withFragment($fragment);

        $this->assertNotSame($uri, $newUri);
        $this->assertNull($uri->fragment);
        $this->assertSame('section1', (string) $newUri->fragment);
    }

    public function test_it_should_check_validity_statistically(): void
    {
        $this->assertTrue(Uri::isValid('https://example.com/foo'));
        $this->assertTrue(Uri::isValid('https://example.com:8080/path?query#fragment'));
    }
}
