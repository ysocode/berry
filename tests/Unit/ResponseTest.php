<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ResponseTest extends TestCase
{
    public function test_it_should_construct_with_defaults(): void
    {
        $response = new Response;

        $this->assertSame(Status::OK, $response->status);
        $this->assertNull($response->body);
    }

    public function test_it_should_set_and_get_header(): void
    {
        $header = new Header(new HeaderName('Content-Type'), ['application/json']);
        $response = new Response()->withHeader($header);

        $fetched = $response->getHeader(new HeaderName('Content-Type'));

        $this->assertNotNull($fetched);
        $this->assertTrue($fetched->equals($header));
    }

    public function test_it_should_return_null_for_missing_header(): void
    {
        $response = new Response;

        $this->assertNull($response->getHeader(new HeaderName('X-Missing')));
    }

    public function test_it_should_check_if_header_exists(): void
    {
        $header = new Header(new HeaderName('X-Test'), ['true']);
        $response = new Response()->withHeader($header);

        $this->assertTrue($response->hasHeader(new HeaderName('X-Test')));
        $this->assertFalse($response->hasHeader(new HeaderName('X-Other')));
    }

    public function test_it_should_add_header_immutably(): void
    {
        $original = new Response;
        $header = new Header(new HeaderName('X-Immut'), ['yes']);
        $modified = $original->withHeader($header);

        $this->assertNotSame($original, $modified);
        $this->assertNull($original->getHeader(new HeaderName('X-Immut')));
        $this->assertNotNull($modified->getHeader(new HeaderName('X-Immut')));
    }

    public function test_it_should_remove_header_immutably(): void
    {
        $header = new Header(new HeaderName('X-Remove'), ['delete-me']);
        $response = new Response()->withHeader($header);
        $newResponse = $response->withoutHeader(new HeaderName('X-Remove'));

        $this->assertNotSame($response, $newResponse);
        $this->assertNotNull($response->getHeader(new HeaderName('X-Remove')));
        $this->assertNull($newResponse->getHeader(new HeaderName('X-Remove')));
    }

    public function test_it_should_replace_body_immutably(): void
    {
        $body = new StreamFactory()->createFromString('Hello, Stream!');

        $response = new Response;
        $modified = $response->withBody($body);

        $this->assertNotSame($response, $modified);
        $this->assertNull($response->body);
        $this->assertSame($body, $modified->body);
    }

    public function test_it_should_replace_status_immutably(): void
    {
        $response = new Response(Status::OK);
        $newResponse = $response->withStatus(Status::NOT_FOUND);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame(Status::OK, $response->status);
        $this->assertSame(Status::NOT_FOUND, $newResponse->status);
    }

    public function test_it_should_not_clone_if_same_status_or_body(): void
    {
        $body = new StreamFactory()->createFromString('Hello, Stream!');
        $response = new Response(Status::OK)->withBody($body);

        $sameStatus = $response->withStatus(Status::OK);
        $sameBody = $response->withBody($body);

        $this->assertSame($response, $sameStatus);
        $this->assertSame($response, $sameBody);
    }

    public function test_it_should_treat_header_names_as_case_insensitive(): void
    {
        $header = new Header(new HeaderName('X-Test'), ['value']);
        $response = new Response()->withHeader($header);

        $this->assertTrue($response->hasHeader(new HeaderName('x-test')));
        $this->assertTrue($response->hasHeader(new HeaderName('X-TEST')));
        $this->assertNotNull($response->getHeader(new HeaderName('x-tEsT')));
    }

    public function test_it_should_overwrite_header_with_same_name(): void
    {
        $original = new Header(new HeaderName('X-Test'), ['original']);
        $updated = new Header(new HeaderName('x-test'), ['updated']);

        $response = new Response()->withHeader($original);
        $newResponse = $response->withHeader($updated);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame(['updated'], $newResponse->getHeader(new HeaderName('X-Test'))?->value);
    }

    public function test_it_should_throw_exception_for_invalid_header_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Each header must be an instance of Header.');

        /** @phpstan-ignore-next-line */
        new Response(Status::OK, [new stdClass]);
    }

    public function test_it_should_not_duplicate_headers_with_different_casing(): void
    {
        $header1 = new Header(new HeaderName('Content-Type'), ['application/json']);
        $header2 = new Header(new HeaderName('content-type'), ['text/html']);

        $response = new Response()->withHeader($header1)->withHeader($header2);

        $this->assertSame(['text/html'], $response->getHeader(new HeaderName('CONTENT-TYPE'))?->value);
    }
}
