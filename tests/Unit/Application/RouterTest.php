<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
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
        )->setName(new Name('home'));

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $route = $routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('GET', $route->method->value);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_register_a_put_route(): void
    {
        $router = new Router;

        $router->put(
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.put'));

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $route = $routeRegistry->getRouteByName(new Name('users.update.put'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('PUT', $route->method->value);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_post_route(): void
    {
        $router = new Router;

        $router->post(
            new Path('/sign-up'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::CREATED)
        )->setName(new Name('signUp'));

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $route = $routeRegistry->getRouteByName(new Name('signUp'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('POST', $route->method->value);
        $this->assertEquals('/sign-up', (string) $route->path);
    }

    public function test_it_should_register_a_delete_route(): void
    {
        $router = new Router;

        $router->delete(
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.destroy'));

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $route = $routeRegistry->getRouteByName(new Name('users.destroy'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('DELETE', $route->method->value);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_patch_route(): void
    {
        $router = new Router;

        $router->patch(
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.patch'));

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $route = $routeRegistry->getRouteByName(new Name('users.update.patch'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('PATCH', $route->method->value);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_named_route(): void
    {
        $router = new Router;

        $router->get(
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $router->put(
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.put'));

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $homeRoute = $routeRegistry->getRouteByName(new Name('home'));
        $usersUpdateRoute = $routeRegistry->getRouteByName(new Name('users.update.put'));

        $this->assertInstanceOf(Route::class, $homeRoute);
        $this->assertInstanceOf(Route::class, $usersUpdateRoute);
        $this->assertEquals('home', (string) $homeRoute->name);
        $this->assertEquals('users.update.put', (string) $usersUpdateRoute->name);
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

    public function test_it_should_add_middleware_to_a_route(): void
    {
        $router = new Router;

        $router->get(
            new Path('/dashboard'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )
            ->setName(new Name('dashboard'))
            ->addMiddleware(
                fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
            );

        $reflection = new ReflectionObject($router);
        $property = $reflection->getProperty('routeRegistry');

        /** @var RouteRegistry $routeRegistry */
        $routeRegistry = $property->getValue($router);

        $route = $routeRegistry->getRouteByName(new Name('dashboard'));

        $this->assertInstanceOf(Route::class, $route);

        $middleware = $route->middlewares[0];

        $this->assertInstanceOf(Closure::class, $middleware);
    }

    public function test_it_should_not_register_a_duplicated_route_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "duplicated" already exists.');

        $router = new Router;

        $router->get(
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));

        $router->post(
            new Path('/login'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));
    }
}
