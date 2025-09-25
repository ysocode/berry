<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\RouteEvent;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteTest extends TestCase
{
    public function test_it_should_create_a_valid_route(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK),
            new Name('home')
        );

        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
        $this->assertEquals('home', (string) $route->name);
    }

    public function test_it_should_add_middleware_to_a_route(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->addMiddleware(
            fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
        );

        $this->assertNotEmpty($route->middlewares);
    }

    public function test_it_should_emit_an_event_when_name_changes(): void
    {
        $eventTriggered = false;

        $route = new Route(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $route->on(
            RouteEvent::NAME_CHANGED,
            function (Route $routeReceived, array $data) use (&$eventTriggered, $route): void {
                $eventTriggered = true;

                $name = $data['name'] ?? null;

                $this->assertSame($route, $routeReceived);
                $this->assertInstanceOf(Name::class, $name);
                $this->assertEquals('home', (string) $name);
            }
        );

        $route->setName(new Name('home'));

        $this->assertTrue($eventTriggered);
    }
}
