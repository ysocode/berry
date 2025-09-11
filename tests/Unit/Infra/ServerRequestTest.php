<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\ValueObjects\Attribute;
use YSOCode\Berry\Domain\ValueObjects\AttributeName;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpVersion;
use YSOCode\Berry\Domain\ValueObjects\RequestTarget;
use YSOCode\Berry\Domain\ValueObjects\UploadStatus;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UploadedFile;
use YSOCode\Berry\Infra\Http\UploadedFileFactory;
use YSOCode\Berry\Infra\Http\UriFactory;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ServerRequestTest extends TestCase
{
    private function createServerRequest(): ServerRequest
    {
        $json = json_encode(['owner' => 'YSO Code', 'lib' => 'Berry']);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to decode JSON.');
        }

        $body = new StreamFactory()->createFromString($json);

        return new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com'),
            [
                new Header(new HeaderName('Content-Type'), ['application/json; charset=utf-8']),
                new Header(new HeaderName('Accept'), ['application/json; charset=utf-8']),
            ],
            $body,
            attributes: [
                new Attribute(new AttributeName('generic-attribute'), 'Berry is the best.'),
            ]
        );
    }

    public function test_it_should_create_a_valid_server_request(): void
    {
        $serverRequest = $this->createServerRequest();

        $contentTypeHeader = $serverRequest->getHeader(new HeaderName('Content-Type'));
        $acceptHeader = $serverRequest->getHeader(new HeaderName('Accept'));

        $this->assertEquals(HttpMethod::GET, $serverRequest->method);
        $this->assertEquals('https://example.com', (string) $serverRequest->uri);
        $this->assertEquals('Content-Type: application/json; charset=utf-8', $contentTypeHeader);
        $this->assertEquals('Accept: application/json; charset=utf-8', $acceptHeader);
        $this->assertJson((string) $serverRequest->body);
    }

    public function test_it_should_return_cloned_server_request_with_updated_method(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withMethod(HttpMethod::PUT);

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals(HttpMethod::PUT, $newServerRequest->method);
    }

    public function test_it_should_return_cloned_server_request_with_updated_uri(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withUri(new UriFactory()->createFromString('https://example.com/path/to/resource?query=param'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals('https://example.com/path/to/resource?query=param', (string) $newServerRequest->uri);
    }

    public function test_it_should_return_cloned_server_request_with_updated_target(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withTarget(new RequestTarget('/path/to/resource?query=param'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals('/path/to/resource?query=param', $newServerRequest->target);
        $this->assertEquals('https://example.com', (string) $newServerRequest->uri);
    }

    public function test_it_should_check_header_existence(): void
    {
        $serverRequest = $this->createServerRequest();

        $this->assertTrue($serverRequest->hasHeader(new HeaderName('Content-Type')));
        $this->assertFalse($serverRequest->hasHeader(new HeaderName('Origin')));
    }

    public function test_it_should_return_cloned_server_request_with_updated_or_new_header(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withHeader(new Header(new HeaderName('Accept'), ['text/html']));
        $newServerRequest = $newServerRequest->withHeader(new Header(new HeaderName('Origin'), ['https://ysocode.com']));

        $acceptHeader = $newServerRequest->getHeader(new HeaderName('Accept'));
        $originHeader = $newServerRequest->getHeader(new HeaderName('Origin'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertInstanceOf(Header::class, $acceptHeader);
        $this->assertInstanceOf(Header::class, $originHeader);
        $this->assertEquals('Accept: text/html', (string) $acceptHeader);
        $this->assertEquals('Origin: https://ysocode.com', (string) $originHeader);
    }

    public function test_it_should_return_cloned_server_request_with_added_header_values(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withAddedHeader(
            new Header(new HeaderName('Accept-Language'), ['pt-BR'])
        );
        $newServerRequest = $newServerRequest->withAddedHeader(
            new Header(new HeaderName('Accept-Language'), ['en-US', 'fr-FR;q=0.8'])
        );

        $acceptLanguageHeader = $newServerRequest->getHeader(new HeaderName('Accept-Language'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertInstanceOf(Header::class, $acceptLanguageHeader);
        $this->assertEquals('Accept-Language: pt-BR, en-US, fr-FR;q=0.8', (string) $acceptLanguageHeader);
    }

    public function test_it_should_return_cloned_server_request_without_an_indicated_header(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withoutHeader(new HeaderName('Content-Type'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertTrue($serverRequest->hasHeader(new HeaderName('Content-Type')));
        $this->assertFalse($newServerRequest->hasHeader(new HeaderName('Content-Type')));
    }

    public function test_it_should_return_cloned_server_request_with_updated_body(): void
    {
        $serverRequest = $this->createServerRequest();

        $json = json_encode(['warning' => 'Berry is the best.']);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to decode JSON.');
        }

        $newBody = new StreamFactory()->createFromString($json);
        $newServerRequest = $serverRequest->withBody($newBody);

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertSame($newBody, $newServerRequest->body);
    }

    public function test_it_should_return_cloned_server_request_with_updated_protocol_version(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withVersion(new HttpVersion('2.0'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals('2.0', (string) $newServerRequest->version);
    }

    public function test_it_should_return_cloned_server_request_with_updated_cookie_params(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withCookieParams([
            '_ga' => 'GA1.1.700403314.1753901012',
            '_ga_GBVEKN2FFG' => 'GS2.1.s1754575621$o1$g1$t1754577353$j60$l0$h0',
            '_ga_BCNNFTTB57' => 'GS2.1.s1754938232$o1$g1$t1754938837$j60$l0$h0',
            '_ga_WFERXYKPPF' => 'GS2.1.s1755193176$o5$g1$t1755194514$j60$l0$h0',
            '_ga_48TNLV2CVZ' => 'GS2.1.s1755193177$o5$g1$t1755194514$j60$l0$h0',
            '_ga_F75NKY8K46' => 'GS2.1.s1755195347$o1$g1$t1755196281$j60$l0$h0',
            '_ga_JZ9W8T667F' => 'GS2.1.s1755563079$o5$g0$t1755563079$j60$l0$h178854805',
        ]);

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals('GA1.1.700403314.1753901012', $newServerRequest->cookieParams['_ga']);
        $this->assertEquals('GS2.1.s1754575621$o1$g1$t1754577353$j60$l0$h0', $newServerRequest->cookieParams['_ga_GBVEKN2FFG']);
        $this->assertEquals('GS2.1.s1754938232$o1$g1$t1754938837$j60$l0$h0', $newServerRequest->cookieParams['_ga_BCNNFTTB57']);
        $this->assertEquals('GS2.1.s1755193176$o5$g1$t1755194514$j60$l0$h0', $newServerRequest->cookieParams['_ga_WFERXYKPPF']);
        $this->assertEquals('GS2.1.s1755193177$o5$g1$t1755194514$j60$l0$h0', $newServerRequest->cookieParams['_ga_48TNLV2CVZ']);
        $this->assertEquals('GS2.1.s1755195347$o1$g1$t1755196281$j60$l0$h0', $newServerRequest->cookieParams['_ga_F75NKY8K46']);
        $this->assertEquals('GS2.1.s1755563079$o5$g0$t1755563079$j60$l0$h178854805', $newServerRequest->cookieParams['_ga_JZ9W8T667F']);
    }

    public function test_it_should_return_cloned_server_request_with_updated_query_params(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withQueryParams([
            'query' => 'param',
        ]);

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals('param', $newServerRequest->queryParams['query']);
    }

    public function test_it_should_return_cloned_server_request_with_updated_parsed_body(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withParsedBody([
            'name' => 'John Doe',
            'email' => 'john.doe@ysocode.com',
            'password' => 'berryIsTheBest',
        ]);

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertEquals('John Doe', $newServerRequest->parsedBody['name']);
        $this->assertEquals('john.doe@ysocode.com', $newServerRequest->parsedBody['email']);
        $this->assertEquals('berryIsTheBest', $newServerRequest->parsedBody['password']);
    }

    public function test_it_should_return_cloned_server_request_with_updated_uploaded_files(): void
    {
        $serverRequest = $this->createServerRequest();

        $tempDir = sys_get_temp_dir();
        $tempFilePath = tempnam($tempDir, 'test_');

        try {
            $spec = [
                'name' => 'doc.pdf',
                'full_path' => 'doc.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tempFilePath,
                'error' => 0,
                'size' => 85402,
            ];

            $newServerRequest = $serverRequest->withUploadedFiles([
                'doc' => new UploadedFileFactory()->createFromSpec($spec),
            ]);

            $docUploadedFile = $newServerRequest->uploadedFiles['doc'];

            $this->assertNotSame($serverRequest, $newServerRequest);
            $this->assertInstanceOf(UploadedFile::class, $docUploadedFile);
            $this->assertEquals(UploadStatus::OK, $docUploadedFile->status);
            $this->assertEquals('doc.pdf', (string) $docUploadedFile->name);
            $this->assertEquals('application/pdf', (string) $docUploadedFile->type);
            $this->assertTrue($docUploadedFile->fromWebServer);
        } finally {
            unlink($tempFilePath);
        }
    }

    public function test_it_should_check_attribute_existence(): void
    {
        $serverRequest = $this->createServerRequest();

        $this->assertTrue($serverRequest->hasAttribute(new AttributeName('generic-attribute')));
        $this->assertFalse($serverRequest->hasAttribute(new AttributeName('missing-attribute')));
    }

    public function test_it_should_return_cloned_server_request_with_updated_or_new_attribute(): void
    {
        $serverRequest = $this->createServerRequest();

        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@ysocode.com',
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@ysocode.com',
            ],
        ];

        $newServerRequest = $serverRequest->withAttribute(
            new Attribute(new AttributeName('users'), $users),
        );

        $usersAttribute = $newServerRequest->attributes['users'];

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertInstanceOf(Attribute::class, $usersAttribute);
        $this->assertEquals($users, $usersAttribute->value);
    }

    public function test_it_should_return_cloned_server_request_without_an_indicated_attribute(): void
    {
        $serverRequest = $this->createServerRequest();
        $newServerRequest = $serverRequest->withoutAttribute(new AttributeName('generic-attribute'));

        $this->assertNotSame($serverRequest, $newServerRequest);
        $this->assertTrue($serverRequest->hasAttribute(new AttributeName('generic-attribute')));
        $this->assertFalse($newServerRequest->hasAttribute(new AttributeName('generic-attribute')));
    }
}
