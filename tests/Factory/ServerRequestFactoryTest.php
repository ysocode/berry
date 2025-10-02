<?php

declare(strict_types=1);

namespace Tests\Factory;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\UploadFileStatus;
use YSOCode\Berry\Infra\Http\ServerRequestFactory;
use YSOCode\Berry\Infra\Http\UploadedFile;

final class ServerRequestFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $headers = [
            'HTTP_HOST' => 'ysocode.com',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_PRAGMA' => 'no-cache',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'HTTP_SEC_FETCH_SITE' => 'none',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
            'HTTP_SEC_FETCH_DEST' => 'document',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br, zstd',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9,pt-BR;q=0.8,pt;q=0.7',
            'HTTP_COOKIE' => '_gcl_au=1.1.845465435.1753901012; _ga=GA1.1.700403314.1753901012; _fbp=fb.0.1753901012187.243633908236698624; __utma=111872281.700403314.1753901012.1754418224.1754418224.1; __utmz=111872281.1754418224.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); cookie_notice=1; SL_C_23361dd035530_SID={"8e6f7a9cda789c26c9d13b3d32bb50aed387df72":{"sessionId":"VYnct6u3hhavolOwTq43N","visitorId":"aAnzzELH7fUwkke4m0Voj"}}; _ga_GBVEKN2FFG=GS2.1.s1754575621$o1$g1$t1754577353$j60$l0$h0; _ga_BCNNFTTB57=GS2.1.s1754938232$o1$g1$t1754938837$j60$l0$h0; _ga_WFERXYKPPF=GS2.1.s1755193176$o5$g1$t1755194514$j60$l0$h0; _ga_48TNLV2CVZ=GS2.1.s1755193177$o5$g1$t1755194514$j60$l0$h0; AdoptVisitorId=KYYwRgrALCAmBMBaAHATgIwAZFXiEKE6UiYwA7BBPJiAMyoBs5QA; _ga_F75NKY8K46=GS2.1.s1755195347$o1$g1$t1755196281$j60$l0$h0; PHPSESSID=sts3bql2ktv2qpak35mijh7rtm; _ga_JZ9W8T667F=GS2.1.s1755563079$o5$g0$t1755563079$j60$l0$h178854805',
        ];

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => 'https',
            'SERVER_NAME' => 'ysocode.com',
            'SERVER_PORT' => 443,
            'REQUEST_URI' => '/?query=param',
            'QUERY_STRING' => 'query=param',
            ...$headers,
        ];

        $cookiePairs = explode(';', $headers['HTTP_COOKIE']);
        foreach ($cookiePairs as $pair) {
            [$name, $value] = array_map(trim(...), explode('=', $pair, 2));
            $_COOKIE[$name] = $value;
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
        $_COOKIE = [];
        $_GET = [];
    }

    public function test_it_should_create_a_server_request_from_globals(): void
    {
        $request = new ServerRequestFactory()->fromGlobals();

        $acceptEncodingHeader = $request->getHeader(new HeaderName('Accept-Encoding'));
        $acceptLanguageHeader = $request->getHeader(new HeaderName('Accept-Language'));

        $this->assertEquals(HttpMethod::GET, $request->method);
        $this->assertEquals('https://ysocode.com/?query=param', (string) $request->uri);
        $this->assertInstanceOf(Header::class, $acceptEncodingHeader);
        $this->assertInstanceOf(Header::class, $acceptLanguageHeader);
        $this->assertEquals(['gzip', 'deflate', 'br', 'zstd'], $acceptEncodingHeader->values);
        $this->assertEquals(['en-US', 'en;q=0.9', 'pt-BR;q=0.8', 'pt;q=0.7'], $acceptLanguageHeader->values);
    }

    public function test_it_should_ignore_redirect_header_when_original_exists(): void
    {
        $_SERVER['REDIRECT_HTTP_HOST'] = 'ignored.com';

        $request = new ServerRequestFactory()->fromGlobals();

        $hostHeader = $request->getHeader(new HeaderName('Host'));

        $this->assertInstanceOf(Header::class, $hostHeader);
        $this->assertEquals('Host: ysocode.com', (string) $hostHeader);
    }

    public function test_it_should_include_redirect_header_when_original_not_exists(): void
    {
        $_SERVER['REDIRECT_HTTP_X_CUSTOM'] = 'custom-value';

        $request = new ServerRequestFactory()->fromGlobals();

        $customHeader = $request->getHeader(new HeaderName('X-Custom'));

        $this->assertInstanceOf(Header::class, $customHeader);
        $this->assertEquals('X-Custom: custom-value', (string) $customHeader);

    }

    public function test_it_should_include_server_params_from_globals(): void
    {
        $request = new ServerRequestFactory()->fromGlobals();

        $this->assertEquals('GET', $request->serverParams['REQUEST_METHOD']);
        $this->assertEquals('https', $request->serverParams['REQUEST_SCHEME']);
        $this->assertEquals('ysocode.com', $request->serverParams['SERVER_NAME']);
        $this->assertEquals(443, $request->serverParams['SERVER_PORT']);
        $this->assertEquals('/?query=param', $request->serverParams['REQUEST_URI']);
        $this->assertEquals('query=param', $request->serverParams['QUERY_STRING']);
    }

    public function test_it_should_include_cookie_params_from_globals(): void
    {
        $request = new ServerRequestFactory()->fromGlobals();

        $this->assertEquals('GA1.1.700403314.1753901012', $request->cookieParams['_ga']);
        $this->assertEquals('GS2.1.s1754575621$o1$g1$t1754577353$j60$l0$h0', $request->cookieParams['_ga_GBVEKN2FFG']);
        $this->assertEquals('GS2.1.s1754938232$o1$g1$t1754938837$j60$l0$h0', $request->cookieParams['_ga_BCNNFTTB57']);
        $this->assertEquals('GS2.1.s1755193176$o5$g1$t1755194514$j60$l0$h0', $request->cookieParams['_ga_WFERXYKPPF']);
        $this->assertEquals('GS2.1.s1755193177$o5$g1$t1755194514$j60$l0$h0', $request->cookieParams['_ga_48TNLV2CVZ']);
        $this->assertEquals('GS2.1.s1755195347$o1$g1$t1755196281$j60$l0$h0', $request->cookieParams['_ga_F75NKY8K46']);
        $this->assertEquals('GS2.1.s1755563079$o5$g0$t1755563079$j60$l0$h178854805', $request->cookieParams['_ga_JZ9W8T667F']);
    }

    public function test_it_should_include_query_params_from_globals(): void
    {
        $request = new ServerRequestFactory()->fromGlobals();

        $this->assertEquals('param', $request->queryParams['query']);
    }

    public function test_it_should_include_parsed_body_from_globals(): void
    {
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john.doe@ysocode.com',
            'password' => 'berryIsTheBest',
        ];

        $request = new ServerRequestFactory()->fromGlobals();

        $this->assertEquals('John Doe', $request->parsedBody['name']);
        $this->assertEquals('john.doe@ysocode.com', $request->parsedBody['email']);
        $this->assertEquals('berryIsTheBest', $request->parsedBody['password']);

        $_POST = [];
    }

    public function test_it_should_include_uploaded_files_from_globals(): void
    {
        $tempDir = sys_get_temp_dir();
        $tempFilePath = tempnam($tempDir, 'test_');

        $_FILES = [
            'doc' => [
                'name' => 'doc.pdf',
                'full_path' => 'doc.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tempFilePath,
                'error' => 0,
                'size' => 85402,
            ],
        ];

        try {
            $request = new ServerRequestFactory()->fromGlobals();
            $docUploadedFile = $request->uploadedFiles['doc'];

            $this->assertInstanceOf(UploadedFile::class, $docUploadedFile);
            $this->assertEquals(UploadFileStatus::OK, $docUploadedFile->status);
            $this->assertEquals('doc.pdf', (string) $docUploadedFile->name);
            $this->assertEquals('application/pdf', (string) $docUploadedFile->type);
            $this->assertTrue($docUploadedFile->fromWebServer);
        } finally {
            $_FILES = [];

            unlink($tempFilePath);
        }
    }
}
