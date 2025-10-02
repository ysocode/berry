<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteGroupTest extends TestCase
{
    public function test_it_should_register_a_get_route(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $route = $routeGroup->routes[0];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_register_a_put_route(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->put(
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.put'));

        $route = $routeGroup->routes[0];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::PUT, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_post_route(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->post(
            new UriPath('/sign-up'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::CREATED)
        )->setName(new Name('signUp'));

        $route = $routeGroup->routes[0];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::POST, $route->method);
        $this->assertEquals('/sign-up', (string) $route->path);
    }

    public function test_it_should_register_a_delete_route(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->delete(
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.destroy'));

        $route = $routeGroup->routes[0];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::DELETE, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_patch_route(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->patch(
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.patch'));

        $route = $routeGroup->routes[0];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::PATCH, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_apply_prefix_to_routes(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $route = $routeGroup->routes[0];

        $routeGroup->addPrefix(new UriPath('/api/v1'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/api/v1/', (string) $route->path);
    }

    public function test_it_should_apply_middleware_to_routes(): void
    {
        $routeGroup = new RouteGroup;

        $routeGroup->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $routeGroup->addMiddleware(
            fn (ServerRequest $request, RequestHandlerInterface $handler): Response => $handler->handle($request)
        );

        $route = $routeGroup->routes[0];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
        $this->assertNotEmpty($route->middlewares);
    }
}
