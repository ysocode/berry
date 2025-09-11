<?php

declare(strict_types=1);

namespace Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteCollection;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteRegistryTest extends TestCase
{
    public function test_it_should_create_a_valid_route_registry(): void
    {
        $routeRegistry = new RouteRegistry;
        $reflection = new ReflectionObject($routeRegistry);
        $property = $reflection->getProperty('routeCollections');

        /** @var array<string, RouteCollection> $routeCollections */
        $routeCollections = $property->getValue($routeRegistry);

        foreach (HttpMethod::getValues() as $method) {
            $this->assertArrayHasKey($method, $routeCollections);
        }
    }

    public function test_it_should_register_a_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $routeRegistry->addRoute(
            HttpMethod::PUT,
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.put'));

        $routeRegistry->addRoute(
            HttpMethod::POST,
            new Path('/sign-up'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::CREATED)
        )->setName(new Name('signUp'));

        $routeRegistry->addRoute(
            HttpMethod::DELETE,
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.destroy'));

        $routeRegistry->addRoute(
            HttpMethod::PATCH,
            new Path('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.patch'));

        $homeRoute = $routeRegistry->getRouteByName(new Name('home'));
        $usersUpdatePutRoute = $routeRegistry->getRouteByName(new Name('users.update.put'));
        $signUpRoute = $routeRegistry->getRouteByName(new Name('signUp'));
        $destroyRoute = $routeRegistry->getRouteByName(new Name('users.destroy'));
        $usersUpdatePatchRoute = $routeRegistry->getRouteByName(new Name('users.update.patch'));

        $this->assertInstanceOf(Route::class, $homeRoute);
        $this->assertEquals('GET', $homeRoute->method->value);
        $this->assertEquals('/', (string) $homeRoute->path);

        $this->assertInstanceOf(Route::class, $usersUpdatePutRoute);
        $this->assertEquals('PUT', $usersUpdatePutRoute->method->value);
        $this->assertEquals('/users/8847', (string) $usersUpdatePutRoute->path);

        $this->assertInstanceOf(Route::class, $signUpRoute);
        $this->assertEquals('POST', $signUpRoute->method->value);
        $this->assertEquals('/sign-up', (string) $signUpRoute->path);

        $this->assertInstanceOf(Route::class, $destroyRoute);
        $this->assertEquals('DELETE', $destroyRoute->method->value);
        $this->assertEquals('/users/8847', (string) $destroyRoute->path);

        $this->assertInstanceOf(Route::class, $usersUpdatePatchRoute);
        $this->assertEquals('PATCH', $usersUpdatePatchRoute->method->value);
        $this->assertEquals('/users/8847', (string) $usersUpdatePatchRoute->path);
    }

    public function test_it_should_not_register_a_duplicated_route_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "duplicated" already exists.');

        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));

        $routeRegistry->addRoute(
            HttpMethod::POST,
            new Path('/login'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));
    }

    public function test_it_should_return_a_route_when_name_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $homeRoute = $routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $homeRoute);
        $this->assertEquals('GET', $homeRoute->method->value);
        $this->assertEquals('/', (string) $homeRoute->path);
    }

    public function test_it_should_return_true_when_route_name_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $this->assertTrue($routeRegistry->hasRouteByName(new Name('home')));
    }

    public function test_it_should_return_a_route_when_method_and_path_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $homeRoute = $routeRegistry->getRouteByMethodAndPath(HttpMethod::GET, new Path('/'));

        $this->assertInstanceOf(Route::class, $homeRoute);
        $this->assertEquals('GET', $homeRoute->method->value);
        $this->assertEquals('/', (string) $homeRoute->path);
    }

    public function test_it_should_return_true_when_route_path_exists(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->addRoute(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $this->assertTrue($routeRegistry->hasRouteByPath(new Path('/')));
    }
}
