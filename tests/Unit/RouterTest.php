<?php

declare(strict_types=1);

namespace Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use YSOCode\Berry\Error;
use YSOCode\Berry\Method;
use YSOCode\Berry\Name;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;
use YSOCode\Berry\Route;
use YSOCode\Berry\Router;
use YSOCode\Berry\Status;

final class RouterTest extends TestCase
{
    public function test_add_and_match_route(): void
    {
        $router = new Router;

        $handler = fn (): string => 'handler';

        $path = new Path('/test');
        $router->get($path, $handler);

        $route = $router->getMatchedRoute(new Request(Method::GET, $path));
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($handler, $route->handler);
    }

    public function test_add_route_throws_exception_if_duplicate(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route GET /duplicate already exists.');

        $router = new Router;

        $path = new Path('/duplicate');
        $router->get($path, fn (): string => 'first');
        $router->get($path, fn (): string => 'second');
    }

    public function test_match_returns_error_for_not_found(): void
    {
        $router = new Router;

        $result = $router->getMatchedRoute(new Request(Method::GET, new Path('/nope')));
        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Route not found.', (string) $result);
    }

    public function test_match_returns_error_for_method_not_allowed(): void
    {
        $router = new Router;

        $path = new Path('/path');
        $router->get($path, fn (): string => 'ok');

        $result = $router->getMatchedRoute(new Request(Method::POST, $path));
        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Method not allowed.', (string) $result);
    }

    public function test_it_registers_routes_for_all_methods(): void
    {
        $router = new Router;
        $path = new Path('/resource');

        $router->get($path, fn (): string => 'get');
        $router->post($path, fn (): string => 'post');
        $router->put($path, fn (): string => 'put');
        $router->delete($path, fn (): string => 'delete');
        $router->patch($path, fn (): string => 'patch');

        $methods = [
            Method::GET,
            Method::POST,
            Method::PUT,
            Method::DELETE,
            Method::PATCH,
        ];

        foreach ($methods as $method) {
            $route = $router->getMatchedRoute(new Request($method, $path));
            $this->assertEquals($method, $route->method);
        }
    }

    public function test_registered_paths_is_updated_on_add_route(): void
    {
        $router = new Router;
        $path = new Path('/registered');

        $router->get($path, fn (): string => 'ok');

        $reflection = new ReflectionClass($router);
        $property = $reflection->getProperty('registeredPaths');
        $registeredPaths = $property->getValue($router);

        $this->assertArrayHasKey((string) $path, $registeredPaths);
        $this->assertTrue($registeredPaths[(string) $path]);
    }

    public function test_named_route_is_registered_and_retrievable(): void
    {
        $router = new Router;

        $router->get(
            new Path('/home'),
            fn (): Response => new Response(Status::OK, 'home'),
            new Name('home')
        );

        $route = $router->getRouteByName('home');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/home', (string) $route->path);
        $this->assertEquals('home', $route->name);
        $this->assertEquals(Method::GET, $route->method);
    }

    public function test_named_route_returns_null_when_not_found(): void
    {
        $router = new Router;

        $this->assertNull($router->getRouteByName('non-existent'));
    }

    public function test_duplicate_route_name_throws_exception(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route name "duplicate" already exists.');

        $router = new Router;

        $router->get(
            new Path('/a'),
            fn (): Response => new Response(Status::OK, 'A'),
            new Name('duplicate')
        );
        $router->get(
            new Path('/b'),
            fn (): Response => new Response(Status::OK, 'B'),
            new Name('duplicate')
        );
    }
}
