<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\ValueObjects\Attribute;
use YSOCode\Berry\Domain\ValueObjects\AttributeName;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class BerryTest extends TestCase
{
    private Berry $berry;

    /**
     * @var array<array{header: string, replace: bool, statusCode: int}>
     */
    private array $emittedHeaders = [];

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

        $this->berry = new Berry(
            new Container,
            responseEmitter: new ResponseEmitter($this->headerEmitter(...)),
        );

        $this->berry->get(
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(
                HttpStatus::OK,
                body: new StreamFactory()->createFromString('Hello, world!')
            ),
        );
    }

    private function headerEmitter(string $header, bool $replace = true, int $statusCode = 0): void
    {
        $this->emittedHeaders[] = [
            'header' => $header,
            'replace' => $replace,
            'statusCode' => $statusCode,
        ];
    }

    public function test_it_should_run_a_route(): void
    {
        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Hello, world!', $output);
    }

    public function test_it_should_handle_global_middlewares(): void
    {
        $this->berry->addMiddleware(function (ServerRequest $request, RequestHandlerInterface $handler): Response {
            $request = $request->withAttribute(
                new Attribute(new AttributeName('important-warning'), 'Berry is the best.')
            );

            return $handler->handle($request);
        });

        $this->berry->addMiddleware(function (ServerRequest $request, RequestHandlerInterface $handler): Response {
            $response = $handler->handle($request);

            $importantWarningAttribute = $request->getAttribute(new AttributeName('important-warning'));
            if (! $importantWarningAttribute instanceof Attribute) {
                return $response->withStatus(HttpStatus::BAD_REQUEST)
                    ->withBody(
                        new StreamFactory()->createFromString('Important warning attribute is not defined.')
                    );
            }

            if (! is_string($importantWarningAttribute->value)) {
                return $response->withStatus(HttpStatus::BAD_REQUEST)
                    ->withBody(
                        new StreamFactory()->createFromString('Important warning attribute value must be a string.')
                    );
            }

            return $response->withStatus(HttpStatus::UNAUTHORIZED)
                ->withBody(
                    new StreamFactory()->createFromString('Replaced by middleware. '.$importantWarningAttribute->value)
                );
        });

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::UNAUTHORIZED, $status);
        $this->assertEquals('Replaced by middleware. Berry is the best.', $output);
    }

    public function test_it_should_handle_method_not_allowed_error(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->berry->run();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::METHOD_NOT_ALLOWED, $status);
    }

    public function test_it_should_handle_not_found_error(): void
    {
        $_SERVER['REQUEST_URI'] = '/path/to/resource?query=param';

        $this->berry->run();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::NOT_FOUND, $status);
    }
}
