<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Domain\ValueObjects\UriPath;

final class RouteRegistryTest extends TestCase
{
    public function test_it_should_add_a_route_for_a_specific_method(): void
    {
        $routeRegistry = new RouteRegistry;

        foreach (HttpMethod::cases() as $method) {
            $routeRegistry->map($method, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

            $route = $routeRegistry->getRouteByMethodAndPath($method, new UriPath('/users/42'));

            $this->assertInstanceOf(Route::class, $route);
            $this->assertEquals($method, $route->method);
            $this->assertEquals('/users/{user}', (string) $route->pathPattern);
            $this->assertEquals(new RequestHandler(HelloWorldHandler::class), $route->handler);
        }
    }

    public function test_it_should_return_error_when_method_not_allowed(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->map(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $error = $routeRegistry->getRouteByMethodAndPath(HttpMethod::DELETE, new UriPath('/users/42'));

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals('Method not allowed.', (string) $error);
    }

    public function test_it_should_return_error_when_route_not_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->map(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $error = $routeRegistry->getRouteByMethodAndPath(HttpMethod::DELETE, new UriPath('/home'));

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals('Route not found.', (string) $error);
    }

    public function test_it_should_reject_duplicated_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "users.show" already exists.');

        $routeRegistry = new RouteRegistry;

        $putProfileRoute = $routeRegistry->map(HttpMethod::PUT, new RoutePathPattern('/users/{user}/profile'), new RequestHandler(HelloWorldHandler::class));
        $getUserRoute = $routeRegistry->map(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $putProfileRoute->setName('users.show');
        $getUserRoute->setName('users.show');
    }
}
