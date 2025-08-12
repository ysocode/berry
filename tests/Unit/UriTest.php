<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;
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

    public function test_it_should_change_attributes(): void
    {
        $uri = new UriFactory()->createFromString('http://user:pass@example.com:8080');

        $newUri = $uri->withScheme(Scheme::HTTPS);
        $this->assertEquals('https://user:pass@example.com:8080', (string) $newUri);

        $newUri = $newUri->withHost(new Host('example.org'));
        $this->assertEquals('https://user:pass@example.org:8080', (string) $newUri);

        $newUri = $newUri->withPort(new Port(1234));
        $this->assertEquals('https://user:pass@example.org:1234', (string) $newUri);

        $newUri = $newUri->withPath(new Path('/newpath'));
        $this->assertEquals('https://user:pass@example.org:1234/newpath', (string) $newUri);

        $newUri = $newUri->withUserInfo(new UserInfo('newuser:newpass'));
        $this->assertEquals('https://newuser:newpass@example.org:1234/newpath', (string) $newUri);

        $newUri = $newUri->withQuery(new Query('newquery=2'));
        $this->assertEquals('https://newuser:newpass@example.org:1234/newpath?newquery=2', (string) $newUri);

        $newUri = $newUri->withFragment(new Fragment('newfrag'));
        $this->assertEquals('https://newuser:newpass@example.org:1234/newpath?newquery=2#newfrag', (string) $newUri);
    }
}
