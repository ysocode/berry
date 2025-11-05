<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteParser;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RoutePathPattern;

final class RouteParserTest extends TestCase
{
    private RouteParser $routeParser;

    protected function setUp(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->map(
            HttpMethod::GET,
            new RoutePathPattern('/users/{user}'),
            new RequestHandler(HelloWorldHandler::class)
        )->setName('users.show');

        $this->routeParser = new RouteParser($routeRegistry);
    }

    public function test_it_should_check_name_existence(): void
    {
        $this->assertTrue($this->routeParser->hasRouteByName('users.show'));
        $this->assertFalse($this->routeParser->hasRouteByName('home'));
    }

    public function test_it_should_return_route_when_name_exists(): void
    {
        $route = $this->routeParser->getRouteByName('users.show');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/users/{user}', (string) $route->pathPattern);
    }
}
