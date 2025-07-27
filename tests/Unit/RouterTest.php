<?php

declare(strict_types=1);

namespace Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use YSOCode\Berry\Error;
use YSOCode\Berry\Method;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Route;
use YSOCode\Berry\Router;

final class RouterTest extends TestCase
{
    public function test_add_and_match_route(): void
    {
        $router = new Router;

        $handler = fn (): string => 'handler';

        $path = new Path('/test');
        $router->get($path, $handler);

        $route = $router->match(new Request(Method::GET, $path));
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

        $result = $router->match(new Request(Method::GET, new Path('/nope')));
        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Route not found.', (string) $result);
    }

    public function test_match_returns_error_for_method_not_allowed(): void
    {
        $router = new Router;

        $path = new Path('/path');
        $router->get($path, fn (): string => 'ok');

        $result = $router->match(new Request(Method::POST, $path));
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
            $route = $router->match(new Request($method, $path));
            $this->assertEquals($method, $route->method);
        }
    }

    public function test_route_exists_returns_true_or_false_correctly(): void
    {
        $router = new Router;
        $path = new Path('/exists');
        $router->get($path, fn (): string => 'exists');

        $this->assertTrue($this->invokeRouteExists($router, Method::GET, $path));
        $this->assertFalse($this->invokeRouteExists($router, Method::POST, $path));
        $this->assertFalse($this->invokeRouteExists($router, Method::GET, new Path('/not-exists')));
    }

    private function invokeRouteExists(Router $router, Method $method, Path $path): bool
    {
        $reflection = new ReflectionClass($router);
        $methodRef = $reflection->getMethod('routeExists');

        return $methodRef->invoke($router, $method, $path);
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
}
