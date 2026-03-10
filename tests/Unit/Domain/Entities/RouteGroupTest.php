<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use Tests\Fixtures\LoggingMiddleware;
use Tests\Fixtures\PoweredByMiddleware;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Types\RouteName;

final class RouteGroupTest extends TestCase
{
    private RouteGroup $routeGroup;

    protected function setUp(): void
    {
        $this->routeGroup = new RouteGroup;

        $this->routeGroup->get('/', HelloWorldHandler::class)->setName('home');
    }

    public function test_it_should_add_single_middleware_to_route(): void
    {
        $route = $this->routeGroup->routeRegistry->getRouteByName(new RouteName('home'));

        $this->routeGroup->appendMiddleware(LoggingMiddleware::class);
        $this->routeGroup->propagate();

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->pathPattern);
        $this->assertNotEmpty($route->middlewareCollection->isNotEmpty());
    }

    public function test_it_should_add_multiple_middlewares_to_route(): void
    {
        $route = $this->routeGroup->routeRegistry->getRouteByName(new RouteName('home'));

        $this->routeGroup->appendMiddlewares([LoggingMiddleware::class, PoweredByMiddleware::class]);
        $this->routeGroup->propagate();

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->pathPattern);
        $this->assertNotEmpty($route->middlewareCollection->isNotEmpty());
    }

    public function test_it_should_add_prefix_to_path_pattern(): void
    {
        $route = $this->routeGroup->routeRegistry->getRouteByName(new RouteName('home'));

        $this->routeGroup->addPrefix('/api/v1');
        $this->routeGroup->propagate();

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/api/v1', (string) $route->pathPattern);
    }

    public function test_it_should_prepend_group_middlewares_before_route_middlewares(): void
    {
        $route = $this->routeGroup->routeRegistry->getRouteByName(new RouteName('home'));
        $this->assertInstanceOf(Route::class, $route);

        $route->appendMiddleware(PoweredByMiddleware::class);

        $this->routeGroup->appendMiddleware(LoggingMiddleware::class);
        $this->routeGroup->propagate();

        $middlewares = $route->middlewareCollection->middlewares;

        $this->assertCount(2, $middlewares);
        $this->assertSame(LoggingMiddleware::class, $middlewares[0]->value);
        $this->assertSame(PoweredByMiddleware::class, $middlewares[1]->value);
    }
}
