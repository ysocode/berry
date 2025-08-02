<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\FileName;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\ServerRequest;
use YSOCode\Berry\Infra\StreamFactory;
use YSOCode\Berry\Infra\UploadedFile;
use YSOCode\Berry\Infra\Uri;

final class ServerRequestTest extends TestCase
{
    private Request $request;

    private Uri $uri;

    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        $this->uri = new Uri('https://example.com');
        $this->request = new Request(Method::GET, $this->uri);
        $this->streamFactory = new StreamFactory;
    }

    public function test_it_should_construct_with_uploaded_files(): void
    {
        $stream = $this->streamFactory->createFromString('file content');
        $uploadedFile = new UploadedFile($stream, 12, null, new FileName('file1.txt'), 'text/plain');

        $serverRequest = new ServerRequest($this->request, uploadedFiles: [$uploadedFile]);

        $this->assertSame($uploadedFile, $serverRequest->getUploadedFile(new FileName('file1.txt')));
    }

    public function test_it_should_throw_if_uploaded_files_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore-next-line */
        new ServerRequest($this->request, uploadedFiles: ['not an uploaded file']);
    }

    public function test_it_should_get_and_check_headers(): void
    {
        $header = new Header(new HeaderName('X-Test'), ['value']);
        $request = $this->request->withHeader($header);
        $serverRequest = new ServerRequest($request);

        $this->assertTrue($serverRequest->hasHeader(new HeaderName('X-Test')));
        $this->assertSame(['value'], $serverRequest->getHeader(new HeaderName('X-Test'))?->value);
    }

    public function test_it_should_add_and_remove_headers_immutably(): void
    {
        $header = new Header(new HeaderName('X-Foo'), ['bar']);
        $serverRequest = new ServerRequest($this->request);

        $newRequest = $serverRequest->withHeader($header);
        $this->assertNull($serverRequest->getHeader(new HeaderName('X-Foo')));
        $this->assertSame(['bar'], $newRequest->getHeader(new HeaderName('X-Foo'))?->value);

        $removedRequest = $newRequest->withoutHeader(new HeaderName('X-Foo'));
        $this->assertNull($removedRequest->getHeader(new HeaderName('X-Foo')));
    }

    public function test_it_should_replace_body_method_uri_immutably(): void
    {
        $serverRequest = new ServerRequest($this->request);
        $newBody = $this->streamFactory->createFromString('body content');
        $newMethod = Method::POST;
        $newUri = new Uri('https://example.org');

        $requestWithBody = $serverRequest->withBody($newBody);
        $requestWithMethod = $serverRequest->withMethod($newMethod);
        $requestWithUri = $serverRequest->withUri($newUri);

        $this->assertNotSame($serverRequest, $requestWithBody);
        $this->assertNotSame($serverRequest, $requestWithMethod);
        $this->assertNotSame($serverRequest, $requestWithUri);
    }

    public function test_it_should_handle_server_cookie_query_parsed_body_and_attributes(): void
    {
        $serverRequest = new ServerRequest($this->request);

        $serverParams = ['SERVER_NAME' => 'localhost'];
        $cookieParams = ['cookie1' => 'value1'];
        $queryParams = ['q' => 'test'];
        $parsedBody = ['field' => 'value'];

        $sr = $serverRequest
            ->withServerParams($serverParams)
            ->withCookieParams($cookieParams)
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody)
            ->withAttribute('attr1', 'val1');

        $this->assertSame($serverParams, $sr->serverParams);
        $this->assertSame($cookieParams, $sr->cookieParams);
        $this->assertSame($queryParams, $sr->queryParams);
        $this->assertSame($parsedBody, $sr->parsedBody);
        $this->assertSame('val1', $sr->getAttribute('attr1'));
    }

    public function test_it_should_remove_attributes(): void
    {
        $serverRequest = new ServerRequest($this->request);
        $sr = $serverRequest->withAttribute('test', 'val');
        $sr2 = $sr->withoutAttribute('test');

        $this->assertNull($sr2->getAttribute('test'));
    }
}
