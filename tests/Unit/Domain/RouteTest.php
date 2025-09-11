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

        $this->assertEquals('GET', $route->method->value);
        $this->assertEquals('/', (string) $route->path);
        $this->assertEquals('home', (string) $route->name);
    }

    public function test_it_should_emit_an_event_when_name_changes(): void
    {
        $called = false;
        $routeReceived = null;
        $dataReceived = null;

        $closure = function (Route $route, array $data) use (&$called, &$routeReceived, &$dataReceived): void {
            $called = true;
            $routeReceived = $route;
            $dataReceived = $data;
        };

        $route = new Route(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )
            ->on(RouteEvent::NAME_CHANGED, $closure)
            ->setName(new Name('home'));

        $name = $dataReceived['name'] ?? null;

        $this->assertTrue($called);
        $this->assertSame($route, $routeReceived);
        $this->assertInstanceOf(Name::class, $name);
        $this->assertEquals('home', (string) $name);
    }
}
