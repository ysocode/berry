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
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class DispatcherTest extends TestCase
{
    private function createServerRequest(): ServerRequest
    {
        return new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com')
        );
    }

    private function createDispatcher(): Dispatcher
    {
        $router = new Router;

        $router->get(
            new Path('/'),
            DummyHandler::class
        )
            ->setName(new Name('home'))
            ->addMiddleware(
                fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle(
                    $request->withHeader(new Header(new HeaderName('X-Request-ID'), ['req-123456']))
                        ->withHeader(new Header(new HeaderName('X-Client-Version'), ['1.0.0']))
                )
            );

        return new Dispatcher(new Container, $router);
    }

    public function test_it_should_dispatch_request(): void
    {
        $request = $this->createServerRequest();
        $dispatcher = $this->createDispatcher();

        $middlewareStack = $dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            throw new RuntimeException((string) $middlewareStack);
        }

        $response = $middlewareStack->handle($request);

        $expectedBody = json_encode(['requestId' => 'req-123456'], JSON_PRETTY_PRINT);
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
