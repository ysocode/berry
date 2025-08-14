<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
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

    public function test_it_should_return_cloned_response_with_added_or_updated_header(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withHeader(
            new Header(new HeaderName('Content-Type'), ['text/css'])
        );

        $contentTypeHeader = $newResponse->getHeader(new HeaderName('Content-Type'));

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals('Content-Type: text/css', (string) $contentTypeHeader);
    }

    public function test_it_should_return_cloned_response_without_an_indicated_header(): void
    {
        $response = $this->createResponse();
        $newResponse = $response->withoutHeader(new HeaderName('Content-Type'));

        $this->assertNotSame($response, $newResponse);
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
        $newResponse = $response->withProtocolVersion('1.2');

        $this->assertNotSame($response, $newResponse);
        $this->assertEquals('1.2', $newResponse->protocolVersion);
    }
}
