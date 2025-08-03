<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Infra\Http\Request;
use YSOCode\Berry\Infra\Http\Uri;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class RequestTest extends TestCase
{
    public function test_it_should_set_and_get_header(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $header = new Header(new HeaderName('Content-Type'), ['application/json']);

        $request = new Request($method, $uri, [$header]);

        $this->assertSame(['application/json'], $request->getHeader(new HeaderName('Content-Type'))?->value);
    }

    public function test_it_should_return_null_for_missing_header(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $request = new Request($method, $uri);

        $this->assertNull($request->getHeader(new HeaderName('Authorization')));
    }

    public function test_it_should_check_if_header_exists(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $header = new Header(new HeaderName('X-Test'), ['abc']);
        $request = new Request($method, $uri, [$header]);

        $this->assertTrue($request->hasHeader(new HeaderName('x-test')));
        $this->assertFalse($request->hasHeader(new HeaderName('not-found')));
    }

    public function test_it_should_add_header_immutably(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $request = new Request($method, $uri);

        $newHeader = new Header(new HeaderName('X-Foo'), ['bar']);
        $newRequest = $request->withHeader($newHeader);

        $this->assertNull($request->getHeader(new HeaderName('X-Foo')));
        $this->assertSame(['bar'], $newRequest->getHeader(new HeaderName('X-Foo'))?->value);
    }

    public function test_it_should_remove_header_immutably(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $header = new Header(new HeaderName('X-Foo'), ['bar']);
        $request = new Request($method, $uri, [$header]);

        $newRequest = $request->withoutHeader(new HeaderName('X-Foo'));

        $this->assertNotNull($request->getHeader(new HeaderName('X-Foo')));
        $this->assertNull($newRequest->getHeader(new HeaderName('X-Foo')));
    }

    public function test_it_should_ignore_removal_of_nonexistent_header(): void
    {
        $method = Method::POST;
        $uri = new Uri('https://example.com');
        $request = new Request($method, $uri);

        $newRequest = $request->withoutHeader(new HeaderName('X-Missing'));

        $this->assertSame($request, $newRequest);
    }

    public function test_it_should_replace_body_immutably(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $request = new Request($method, $uri);

        $originalBody = $request->body;
        $newBody = new StreamFactory()->createFromString();

        $newRequest = $request->withBody($newBody);

        $this->assertNotSame($request, $newRequest);
        $this->assertNotSame($request->body, $newRequest->body);
        $this->assertSame($newBody, $newRequest->body);
        $this->assertSame($originalBody, $request->body);
    }

    public function test_it_should_replace_method_immutably(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $request = new Request($method, $uri);

        $newRequest = $request->withMethod(Method::POST);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame(Method::GET, $request->method);
        $this->assertSame(Method::POST, $newRequest->method);
    }

    public function test_it_should_replace_uri_immutably(): void
    {
        $method = Method::GET;
        $uri = new Uri('https://example.com');
        $request = new Request($method, $uri);

        $newUri = new Uri('https://example.org');
        $newRequest = $request->withUri($newUri);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame($uri, $request->uri);
        $this->assertSame($newUri, $newRequest->uri);
    }
}
