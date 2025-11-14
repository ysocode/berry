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
use YSOCode\Berry\Domain\Types\UriPath;

final class RouteParserTest extends TestCase
{
    private RouteParser $routeParser;

    protected function setUp(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->map(
            HttpMethod::GET,
            new RoutePathPattern('/users'),
            new RequestHandler(HelloWorldHandler::class)
        )->setName('users.index');

        $routeRegistry->map(
            HttpMethod::GET,
            new RoutePathPattern('/users/{user}'),
            new RequestHandler(HelloWorldHandler::class)
        )->setName('users.show');

        $this->routeParser = new RouteParser($routeRegistry, new UriPath('/berry'));
    }

    public function test_it_should_check_route_existence(): void
    {
        $this->assertTrue($this->routeParser->hasRouteByName('users.show'));
        $this->assertFalse($this->routeParser->hasRouteByName('home'));
    }

    public function test_it_should_return_route_when_route_exists(): void
    {
        $route = $this->routeParser->getRouteByName('users.show');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/users/{user}', (string) $route->pathPattern);
    }

    public function test_it_should_resolve_path_with_parameters_when_route_exists(): void
    {
        $path = $this->routeParser->resolvePathForRouteByName('users.show', ['user' => '42']);

        $this->assertInstanceOf(UriPath::class, $path);
        $this->assertEquals('/berry/users/42', (string) $path);
    }

    public function test_it_should_resolve_path_without_parameters_when_route_exists(): void
    {
        $path = $this->routeParser->resolvePathForRouteByName('users.index');

        $this->assertInstanceOf(UriPath::class, $path);
        $this->assertEquals('/berry/users', (string) $path);
    }

    public function test_it_should_resolve_path_without_base_path_when_route_exists(): void
    {
        $path = $this->routeParser->resolvePathForRouteByName('users.index', withBasePath: false);

        $this->assertInstanceOf(UriPath::class, $path);
        $this->assertEquals('/users', (string) $path);
    }

    public function test_it_should_return_null_when_when_route_not_exists(): void
    {
        $path = $this->routeParser->resolvePathForRouteByName('unknown.route');

        $this->assertNull($path);
    }
}
