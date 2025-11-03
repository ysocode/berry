<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\HttpVersion;
use YSOCode\Berry\Domain\Types\Header;
use YSOCode\Berry\Domain\Types\HeaderName;
use YSOCode\Berry\Infra\Http\Request;
use YSOCode\Berry\Infra\Http\UriFactory;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class RequestTest extends TestCase
{
    private function createRequest(): Request
    {
        $json = json_encode(['owner' => 'YSO Code', 'lib' => 'Berry']);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to decode JSON.');
        }

        $body = new StreamFactory()->createFromString($json);

        return new Request(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com'),
            [
                new Header(new HeaderName('Content-Type'), ['application/json; charset=utf-8']),
                new Header(new HeaderName('Accept'), ['application/json; charset=utf-8']),
            ],
            $body,
        );
    }

    public function test_it_should_create_a_valid_request(): void
    {
        $request = $this->createRequest();

        $contentTypeHeader = $request->getHeader('Content-Type');
        $acceptHeader = $request->getHeader('Accept');

        $this->assertEquals(HttpMethod::GET, $request->method);
        $this->assertEquals('https://example.com', (string) $request->uri);
        $this->assertEquals('Content-Type: application/json; charset=utf-8', (string) $contentTypeHeader);
        $this->assertEquals('Accept: application/json; charset=utf-8', (string) $acceptHeader);
        $this->assertJson((string) $request->body);
    }

    public function test_it_should_return_cloned_request_with_updated_method(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withMethod(HttpMethod::PUT);

        $this->assertNotSame($request, $newRequest);
        $this->assertEquals(HttpMethod::PUT, $newRequest->method);
    }

    public function test_it_should_return_cloned_request_with_updated_uri(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withUri('https://example.com/path/to/resource?query=param');

        $this->assertNotSame($request, $newRequest);
        $this->assertEquals('https://example.com/path/to/resource?query=param', (string) $newRequest->uri);
    }

    public function test_it_should_return_cloned_request_with_updated_target(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withTarget('/path/to/resource?query=param');

        $this->assertNotSame($request, $newRequest);
        $this->assertEquals('/path/to/resource?query=param', (string) $newRequest->target);
        $this->assertEquals('https://example.com', (string) $newRequest->uri);
    }

    public function test_it_should_check_header_existence(): void
    {
        $request = $this->createRequest();

        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertFalse($request->hasHeader('Origin'));
    }

    public function test_it_should_return_cloned_request_with_updated_or_new_header(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withHeader('Accept', ['text/html']);
        $newRequest = $newRequest->withHeader('Origin', ['https://ysocode.com']);

        $acceptHeader = $newRequest->getHeader('Accept');
        $originHeader = $newRequest->getHeader('Origin');

        $this->assertNotSame($request, $newRequest);
        $this->assertInstanceOf(Header::class, $acceptHeader);
        $this->assertInstanceOf(Header::class, $originHeader);
        $this->assertEquals('Accept: text/html', (string) $acceptHeader);
        $this->assertEquals('Origin: https://ysocode.com', (string) $originHeader);
    }

    public function test_it_should_return_cloned_request_with_added_header_values(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withAddedHeader('Accept-Language', ['pt-BR']);
        $newRequest = $newRequest->withAddedHeader('Accept-Language', ['en-US', 'fr-FR;q=0.8']);

        $acceptLanguageHeader = $newRequest->getHeader('Accept-Language');

        $this->assertNotSame($request, $newRequest);
        $this->assertInstanceOf(Header::class, $acceptLanguageHeader);
        $this->assertEquals('Accept-Language: pt-BR, en-US, fr-FR;q=0.8', (string) $acceptLanguageHeader);
    }

    public function test_it_should_return_cloned_request_without_an_indicated_header(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withoutHeader('Content-Type');

        $this->assertNotSame($request, $newRequest);
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertFalse($newRequest->hasHeader('Content-Type'));
    }

    public function test_it_should_return_cloned_request_with_updated_body(): void
    {
        $request = $this->createRequest();

        $json = json_encode(['warning' => 'Berry is the best.']);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to decode JSON.');
        }

        $newRequest = $request->withBody($json);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame($json, (string) $newRequest->body);
    }

    public function test_it_should_return_cloned_request_with_updated_protocol_version(): void
    {
        $request = $this->createRequest();
        $newRequest = $request->withVersion(HttpVersion::V2_0);

        $this->assertNotSame($request, $newRequest);
        $this->assertEquals(HttpVersion::V2_0, $newRequest->version);
    }
}
