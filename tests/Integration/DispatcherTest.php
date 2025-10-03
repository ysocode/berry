<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixtures\InspectRequestHandler;
use Tests\Fixtures\LoggingMiddleware;
use Tests\Fixtures\PoweredByMiddleware;
use YSOCode\Berry\Application\Dispatcher;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
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

        $router->get(new UriPath('/'), InspectRequestHandler::class);

        $dispatcher = new Dispatcher(new Container, $router);

        $middlewareStack = $dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            throw new RuntimeException((string) $middlewareStack);
        }

        $response = $middlewareStack->handle($request);

        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertEquals('Log: No log available. Powered by: Not powered.', (string) $response->body);
    }

    public function test_it_should_dispatch_request_with_single_middleware(): void
    {
        $request = new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com')
        );

        $router = new Router;

        $router->get(
            new UriPath('/'),
            InspectRequestHandler::class
        )->addMiddleware(LoggingMiddleware::class);

        $dispatcher = new Dispatcher(new Container, $router);

        $middlewareStack = $dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            throw new RuntimeException((string) $middlewareStack);
        }

        $response = $middlewareStack->handle($request);

        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Not powered.', (string) $response->body);
    }

    public function test_it_should_dispatch_request_with_multiple_middlewares(): void
    {
        $request = new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com')
        );

        $router = new Router;

        $router->get(
            new UriPath('/'),
            InspectRequestHandler::class
        )
            ->addMiddlewares([LoggingMiddleware::class, PoweredByMiddleware::class]);

        $dispatcher = new Dispatcher(new Container, $router);

        $middlewareStack = $dispatcher->dispatch($request);
        if ($middlewareStack instanceof Error) {
            throw new RuntimeException((string) $middlewareStack);
        }

        $response = $middlewareStack->handle($request);

        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Berry.', (string) $response->body);
    }
}
