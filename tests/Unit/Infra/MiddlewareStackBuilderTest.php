<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\InspectRequestHandler;
use Tests\Fixtures\LoggingMiddleware;
use Tests\Fixtures\PoweredByMiddleware;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Http\MiddlewareStackBuilder;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class MiddlewareStackBuilderTest extends TestCase
{
    public function test_it_should_build_middleware_stack(): void
    {
        $middlewareStackBuilder = new MiddlewareStackBuilder(new Container);
        $middlewareStack = $middlewareStackBuilder->build(
            new InspectRequestHandler,
            [LoggingMiddleware::class, PoweredByMiddleware::class]
        );

        $response = $middlewareStack->handle(
            new ServerRequest(
                HttpMethod::GET,
                new UriFactory()->createFromString('https://example.com')
            )
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(HttpStatus::OK, $response->status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Berry.', (string) $response->body);
    }
}
