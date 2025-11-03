<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Enums\HttpVersion;
use YSOCode\Berry\Domain\Types\Header;
use YSOCode\Berry\Domain\Types\HeaderName;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ResponseTest extends TestCase
{
    private function createResponse(): Response
    {
        $body = new StreamFactory()->createFromString('Hello, world!');

        return new Response(
            HttpStatus::OK,
            [
                new Header(new HeaderName('Content-Type'), ['text/javascript; charset=utf-8']),
                new Header(new HeaderName('Content-Encoding'), ['deflate', 'gzip']),
            ],
            $body,
        );
    }

    public function test_it_should_create_a_valid_response(): void
    {
        $response = $this->createResponse();

        $contentTypeHeader = $response->getHeader('Content-Type');
        $contentEncodingHeader = $response->getHeader('Content-Encoding');

        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertInstanceOf(Header::class, $contentTypeHeader);
        $this->assertInstanceOf(Header::class, $contentEncodingHeader);
        $this->assertEquals('Content-Type: text/javascript; charset=utf-8', (string) $contentTypeHeader);
        $this->assertEquals('Content-Encoding: deflate, gzip', (string) $contentEncodingHeader);
        $this->assertEquals('Hello, world!', (string) $response->body);
    }

    public function test_it_should_return_cloned_response_with_updated_status(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withStatus(HttpStatus::CREATED);

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals(HttpStatus::CREATED, $newResponse->status);
    }

    public function test_it_should_check_header_existence(): void
    {
        $response = $this->createResponse();

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertFalse($response->hasHeader('Content-Disposition'));
    }

    public function test_it_should_return_cloned_response_with_updated_or_new_header(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withHeader('Content-Type', ['text/css']);
        $newResponse = $newResponse->withHeader('Accept', ['text/html']);

        $contentTypeHeader = $newResponse->getHeader('Content-Type');
        $acceptHeader = $newResponse->getHeader('Accept');

        $this->assertNotSame($response, $newResponse);
        $this->assertInstanceOf(Header::class, $contentTypeHeader);
        $this->assertInstanceOf(Header::class, $acceptHeader);
        $this->assertEquals('Content-Type: text/css', (string) $contentTypeHeader);
        $this->assertEquals('Accept: text/html', (string) $acceptHeader);
    }

    public function test_it_should_return_cloned_response_with_added_header_values(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withAddedHeader('Set-Cookie', ['sessionid=38afes7a8; HttpOnly; Path=/']);
        $newResponse = $newResponse->withAddedHeader(
            'Set-Cookie',
            [
                'id=a3fWa; Expires=Wed, 21 Oct 2015 07:28:00 GMT; Secure; HttpOnly',
                'qwerty=219ffwef9w0f; Domain=somecompany.co.uk; Path=/; Expires=Wed, 30 Aug 2019 00:00:00 GMT',
            ]
        );

        $setCookieHeader = $newResponse->getHeader('Set-Cookie');

        $this->assertNotSame($response, $newResponse);
        $this->assertInstanceOf(Header::class, $setCookieHeader);
        $this->assertEquals(
            [
                'sessionid=38afes7a8; HttpOnly; Path=/',
                'id=a3fWa; Expires=Wed, 21 Oct 2015 07:28:00 GMT; Secure; HttpOnly',
                'qwerty=219ffwef9w0f; Domain=somecompany.co.uk; Path=/; Expires=Wed, 30 Aug 2019 00:00:00 GMT',
            ],
            $setCookieHeader->values
        );
    }

    public function test_it_should_return_cloned_response_without_an_indicated_header(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withoutHeader('Content-Type');

        $this->assertNotSame($response, $newResponse);
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertFalse($newResponse->hasHeader('Content-Type'));
    }

    public function test_it_should_return_cloned_response_with_updated_body(): void
    {
        $response = $this->createResponse();

        $json = json_encode(['warning' => 'Berry is the best.']);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to decode JSON.');
        }

        $newResponse = $response->withBody($json);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame($json, (string) $newResponse->body);
    }

    public function test_it_should_return_cloned_response_with_updated_protocol_version(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withVersion(HttpVersion::V2_0);

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals(HttpVersion::V2_0, $newResponse->version);
    }
}
