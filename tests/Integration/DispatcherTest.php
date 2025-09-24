<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixtures\DummyHandler;
use YSOCode\Berry\Application\Dispatcher;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class DispatcherTest extends TestCase
{
    public function test_it_should_dispatch_request(): void
    {
        $request = new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com')
        );

        $router = new Router;

        $router->get(new Path('/'), DummyHandler::class);

        $dispatcher = new Dispatcher(new Container, $router);

        $middlewareStack = $dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            throw new RuntimeException((string) $middlewareStack);
        }

        $response = $middlewareStack->handle($request);

        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertEquals('Hello, World!', (string) $response->body);
    }

    public function test_it_should_dispatch_request_with_middleware(): void
    {
        $request = new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com')
        );

        $router = new Router;

        $router->get(
            new Path('/'),
            DummyHandler::class
        )
            ->addMiddleware(
                fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle(
                    $request->withHeader(new Header(new HeaderName('X-Request-ID'), ['req-123456']))
                        ->withHeader(new Header(new HeaderName('X-Client-Version'), ['1.0.0']))
                )
            );

        $dispatcher = new Dispatcher(new Container, $router);

        $middlewareStack = $dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            throw new RuntimeException((string) $middlewareStack);
        }

        $response = $middlewareStack->handle($request);

        $expectedBody = json_encode(['requestId' => 'req-123456']);
        if (! is_string($expectedBody)) {
            throw new RuntimeException('Expected body to be string.');
        }

        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertJsonStringEqualsJsonString(
            $expectedBody,
            (string) $response->body
        );
    }
}
