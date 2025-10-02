<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixtures\DummyHandler;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class MiddlewareStackBuilderTest extends TestCase
{
    public function test_it_should_build_middleware_stack(): void
    {
        $middlewares = [
            function (ServerRequest $request, RequestHandlerInterface $handler): Response {
                $response = $handler->handle($request);

                return $response->withStatus(HttpStatus::CREATED);
            },
            fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle(
                $request->withHeader(new Header(new HeaderName('X-Request-ID'), ['req-123456']))
            ),
        ];

        $middlewareStackBuilder = new MiddlewareStackBuilder(new Container);
        $middlewareStack = $middlewareStackBuilder->build(
            new DummyHandler,
            $middlewares
        );

        $response = $middlewareStack->handle(
            new ServerRequest(
                HttpMethod::GET,
                new UriFactory()->createFromString('https://example.com')
            )
        );

        $expectedBody = json_encode(['requestId' => 'req-123456']);
        if (! is_string($expectedBody)) {
            throw new RuntimeException('Expected body to be string.');
        }

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(HttpStatus::CREATED, $response->status);
        $this->assertJsonStringEqualsJsonString(
            $expectedBody,
            (string) $response->body
        );
    }
}
