<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
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

        $contentTypeHeader = $response->getHeader(new HeaderName('Content-Type'));
        $contentEncodingHeader = $response->getHeader(new HeaderName('Content-Encoding'));

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

        $this->assertTrue($response->hasHeader(new HeaderName('Content-Type')));
        $this->assertFalse($response->hasHeader(new HeaderName('Content-Disposition')));
    }

    public function test_it_should_return_cloned_response_with_updated_or_new_header(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withHeader(new Header(new HeaderName('Content-Type'), ['text/css']));
        $newResponse = $newResponse->withHeader(new Header(new HeaderName('Accept'), ['text/html']));

        $contentTypeHeader = $newResponse->getHeader(new HeaderName('Content-Type'));
        $acceptHeader = $newResponse->getHeader(new HeaderName('Accept'));

        $this->assertNotSame($response, $newResponse);
        $this->assertInstanceOf(Header::class, $contentTypeHeader);
        $this->assertInstanceOf(Header::class, $acceptHeader);
        $this->assertEquals('Content-Type: text/css', (string) $contentTypeHeader);
        $this->assertEquals('Accept: text/html', (string) $acceptHeader);
    }

    public function test_it_should_return_cloned_response_with_added_header_values(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withAddedHeader(
            new Header(new HeaderName('Set-Cookie'), ['sessionid=38afes7a8; HttpOnly; Path=/'])
        );
        $newResponse = $newResponse->withAddedHeader(
            new Header(
                new HeaderName('Set-Cookie'),
                [
                    'id=a3fWa; Expires=Wed, 21 Oct 2015 07:28:00 GMT; Secure; HttpOnly',
                    'qwerty=219ffwef9w0f; Domain=somecompany.co.uk; Path=/; Expires=Wed, 30 Aug 2019 00:00:00 GMT',
                ]
            )
        );

        $setCookieHeader = $newResponse->getHeader(new HeaderName('Set-Cookie'));

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
        $newResponse = $response->withoutHeader(new HeaderName('Content-Type'));

        $this->assertNotSame($response, $newResponse);
        $this->assertTrue($response->hasHeader(new HeaderName('Content-Type')));
        $this->assertFalse($newResponse->hasHeader(new HeaderName('Content-Type')));
    }

    public function test_it_should_return_cloned_response_with_updated_body(): void
    {
        $response = $this->createResponse();

        $newBody = new StreamFactory()->createFromString('New body.');
        $newResponse = $response->withBody($newBody);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame($newBody, $newResponse->body);
    }

    public function test_it_should_return_cloned_response_with_updated_protocol_version(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withVersion(new HttpVersion('2.0'));

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals('2.0', (string) $newResponse->version);
    }
}
