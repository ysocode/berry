<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use Tests\Fixtures\LoggingMiddleware;
use Tests\Fixtures\PoweredByMiddleware;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\RouteEvent;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RouteName;
use YSOCode\Berry\Domain\Types\RoutePathPattern;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class RouteTest extends TestCase
{
    public function test_it_should_create_a_valid_route(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new RoutePathPattern('/'),
            new RequestHandler(HelloWorldHandler::class),
            new RouteName('home')
        );

        $resolvedHandler = $route->handler->resolve(new Container);
        $response = $resolvedHandler->handle(
            new ServerRequest(
                HttpMethod::GET,
                new UriFactory()->createFromString('https://example.com')
            )
        );

        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->pathPattern);
        $this->assertEquals('home', (string) $route->name);
        $this->assertEquals('Hello, world!', (string) $response->body);
    }

    public function test_it_should_add_single_middleware_to_a_route(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new RoutePathPattern('/'),
            new RequestHandler(HelloWorldHandler::class)
        )->addMiddleware(LoggingMiddleware::class);

        $this->assertNotEmpty($route->middlewareCollection->isNotEmpty());
    }

    public function test_it_should_add_multiple_middleware_to_a_route(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new RoutePathPattern('/'),
            new RequestHandler(HelloWorldHandler::class)
        )->addMiddlewares([LoggingMiddleware::class, PoweredByMiddleware::class]);

        $this->assertNotEmpty($route->middlewareCollection->isNotEmpty());
    }

    public function test_it_should_emit_an_event_when_name_changes(): void
    {
        $eventTriggered = false;

        $route = new Route(
            HttpMethod::GET,
            new RoutePathPattern('/'),
            new RequestHandler(HelloWorldHandler::class)
        );

        $route->on(
            RouteEvent::NAME_CHANGED,
            function (Route $routeReceived, array $data) use (&$eventTriggered, $route): void {
                $eventTriggered = true;

                $name = $data['name'] ?? null;

                $this->assertSame($route, $routeReceived);
                $this->assertInstanceOf(RouteName::class, $name);
                $this->assertEquals('home', (string) $name);
            }
        );

        $route->setName('home');

        $this->assertTrue($eventTriggered);
    }

    public function test_it_should_add_a_prefix_to_path_pattern(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new RoutePathPattern('/'),
            new RequestHandler(HelloWorldHandler::class)
        )
            ->setName('home')
            ->addPrefix('/{slug}');

        $this->assertEquals('/{slug}', (string) $route->pathPattern);
    }
}
