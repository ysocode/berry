<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteRegistryTest extends TestCase
{
    public function test_it_should_create_a_valid_route_registry(): void
    {
        $routeRegistry = new RouteRegistry;
        $reflection = new ReflectionObject($routeRegistry);

        $routeCollections = $reflection->getProperty('routeCollections')->getValue($routeRegistry);
        if (! is_array($routeCollections)) {
            throw new RuntimeException('Route collections should be an array.');
        }

        foreach (HttpMethod::getValues() as $method) {
            $this->assertArrayHasKey($method, $routeCollections);
        }
    }

    public function test_it_should_register_a_get_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $route = $routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_register_a_put_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::PUT,
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.put'));

        $route = $routeRegistry->getRouteByName(new Name('users.update.put'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::PUT, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_post_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::POST,
            new UriPath('/sign-up'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::CREATED)
        )->setName(new Name('signUp'));

        $route = $routeRegistry->getRouteByName(new Name('signUp'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::POST, $route->method);
        $this->assertEquals('/sign-up', (string) $route->path);
    }

    public function test_it_should_register_a_delete_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::DELETE,
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.destroy'));

        $route = $routeRegistry->getRouteByName(new Name('users.destroy'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::DELETE, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_patch_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::PATCH,
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.patch'));

        $route = $routeRegistry->getRouteByName(new Name('users.update.patch'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::PATCH, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_not_register_a_duplicated_route_path_for_the_same_http_method(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route GET / already exists.');

        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );
    }

    public function test_it_should_not_register_a_duplicated_route_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "duplicated" already exists.');

        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));

        $routeRegistry->addRoute(
            HttpMethod::POST,
            new UriPath('/login'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));
    }

    public function test_it_should_return_a_route_when_name_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $homeRoute = $routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $homeRoute);
        $this->assertEquals(HttpMethod::GET, $homeRoute->method);
        $this->assertEquals('/', (string) $homeRoute->path);
    }

    public function test_it_should_return_true_when_route_name_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $this->assertTrue($routeRegistry->hasRouteByName(new Name('home')));
    }

    public function test_it_should_return_a_route_when_method_and_path_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $homeRoute = $routeRegistry->getRouteByMethodAndPath(HttpMethod::GET, new UriPath('/'));

        $this->assertInstanceOf(Route::class, $homeRoute);
        $this->assertEquals(HttpMethod::GET, $homeRoute->method);
        $this->assertEquals('/', (string) $homeRoute->path);
    }

    public function test_it_should_return_true_when_route_path_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $this->assertTrue($routeRegistry->hasRouteByPath(new UriPath('/')));
    }

    public function test_it_should_add_a_route_group(): void
    {
        $routeGroup = new RouteGroup;
        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $routeRegistry = new RouteRegistry;
        $routeRegistry->addGroup($routeGroup);

        $route = $routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_not_register_a_duplicated_route_path_for_the_same_http_method_from_route_group(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route GET / already exists.');

        $routeGroup = new RouteGroup;

        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $routeRegistry = new RouteRegistry;
        $routeRegistry->addGroup($routeGroup);
    }

    public function test_it_should_not_register_a_duplicated_route_name_from_route_group(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "duplicated" already exists.');

        $routeGroup = new RouteGroup;

        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));

        $routeGroup->get(
            new UriPath('/login'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));

        $routeRegistry = new RouteRegistry;
        $routeRegistry->addGroup($routeGroup);
    }
}
