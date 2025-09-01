<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

class RouterTest extends TestCase
{
    public function test_it_should_register_a_get_route(): void
    {
        $router = new Router;

        $router->get(
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $reflection = new ReflectionObject($router);
        $routesProperty = $reflection->getProperty('routes');

        /** @var array<string, array<string, Route>> $routes */
        $routes = $routesProperty->getValue($router);

        $method = HttpMethod::GET;
        $path = new Path('/');

        $route = $routes[$method->value][(string) $path] ?? null;

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('GET', $route->method->value);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_register_a_put_route(): void
    {
        $router = new Router;

        $router->put(new Path('/users/8847'), fn (ServerRequest $request): Response => new Response(HttpStatus::OK));

        $reflection = new ReflectionObject($router);
        $routesProperty = $reflection->getProperty('routes');

        /** @var array<string, array<string, Route>> $routes */
        $routes = $routesProperty->getValue($router);

        $method = HttpMethod::PUT;
        $path = new Path('/users/8847');

        $route = $routes[$method->value][(string) $path] ?? null;

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('PUT', $route->method->value);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_post_route(): void
    {
        $router = new Router;

        $router->post(new Path('/sign-up'), fn (ServerRequest $request): Response => new Response(HttpStatus::CREATED));

        $reflection = new ReflectionObject($router);
        $routesProperty = $reflection->getProperty('routes');

        /** @var array<string, array<string, Route>> $routes */
        $routes = $routesProperty->getValue($router);

        $method = HttpMethod::POST;
        $path = new Path('/sign-up');

        $route = $routes[$method->value][(string) $path] ?? null;

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('POST', $route->method->value);
        $this->assertEquals('/sign-up', (string) $route->path);
    }

    public function test_it_should_register_a_delete_route(): void
    {
        $router = new Router;

        $router->delete(new Path('/users/8847'), fn (ServerRequest $request): Response => new Response(HttpStatus::OK));

        $reflection = new ReflectionObject($router);
        $routesProperty = $reflection->getProperty('routes');

        /** @var array<string, array<string, Route>> $routes */
        $routes = $routesProperty->getValue($router);

        $method = HttpMethod::DELETE;
        $path = new Path('/users/8847');

        $route = $routes[$method->value][(string) $path] ?? null;

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('DELETE', $route->method->value);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_patch_route(): void
    {
        $router = new Router;

        $router->patch(new Path('/users/8847'), fn (ServerRequest $request): Response => new Response(HttpStatus::OK));

        $reflection = new ReflectionObject($router);
        $routesProperty = $reflection->getProperty('routes');

        /** @var array<string, array<string, Route>> $routes */
        $routes = $routesProperty->getValue($router);

        $method = HttpMethod::PATCH;
        $path = new Path('/users/8847');

        $route = $routes[$method->value][(string) $path] ?? null;

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('PATCH', $route->method->value);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_return_a_route_when_exists(): void
    {
        $router = new Router;

        $router->get(
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $route = $router->getMatchedRoute(
            new ServerRequest(HttpMethod::GET, new UriFactory()->createFromString('https://example.com'))
        );

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('GET', $route->method->value);
        $this->assertEquals('/', (string) $route->path);
    }
}
