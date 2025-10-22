<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Domain\ValueObjects\UriPath;

final class RouteRegistryTest extends TestCase
{
    public function test_it_should_add_a_route_for_a_specific_method(): void
    {
        $routeRegistry = new RouteRegistry;

        foreach (HttpMethod::cases() as $method) {
            $routeRegistry->map(
                $method,
                new RoutePathPattern('/users/{user}'),
                HelloWorldHandler::class
            );

            $route = $routeRegistry->getRouteByMethodAndPath($method, new UriPath('/users/42'));

            $this->assertInstanceOf(Route::class, $route);
            $this->assertEquals($method, $route->method);
            $this->assertEquals('/users/{user}', (string) $route->pathPattern);
            $this->assertEquals(HelloWorldHandler::class, $route->handler);
        }
    }
}
