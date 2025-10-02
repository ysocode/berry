<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;
use YSOCode\Berry\Infra\Stream\StreamFactory;

class RouterTest extends TestCase
{
    public function test_it_should_register_a_get_route(): void
    {
        $router = new Router;

        $router->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('home'));

        $route = $router->routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_register_a_put_route(): void
    {
        $router = new Router;

        $router->put(
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.put'));

        $route = $router->routeRegistry->getRouteByName(new Name('users.update.put'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::PUT, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_post_route(): void
    {
        $router = new Router;

        $router->post(
            new UriPath('/sign-up'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::CREATED)
        )->setName(new Name('signUp'));

        $route = $router->routeRegistry->getRouteByName(new Name('signUp'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::POST, $route->method);
        $this->assertEquals('/sign-up', (string) $route->path);
    }

    public function test_it_should_register_a_delete_route(): void
    {
        $router = new Router;

        $router->delete(
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.destroy'));

        $route = $router->routeRegistry->getRouteByName(new Name('users.destroy'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::DELETE, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_register_a_patch_route(): void
    {
        $router = new Router;

        $router->patch(
            new UriPath('/users/8847'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('users.update.patch'));

        $route = $router->routeRegistry->getRouteByName(new Name('users.update.patch'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::PATCH, $route->method);
        $this->assertEquals('/users/8847', (string) $route->path);
    }

    public function test_it_should_return_a_route_when_request_matches(): void
    {
        $router = new Router;

        $router->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $route = $router->getMatchedRoute(
            new ServerRequest(HttpMethod::GET, new UriFactory()->createFromString('https://example.com'))
        );

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_return_error_when_method_not_allowed(): void
    {
        $router = new Router;

        $router->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        );

        $result = $router->getMatchedRoute(
            new ServerRequest(HttpMethod::POST, new UriFactory()->createFromString('https://example.com'))
        );

        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Method not allowed.', (string) $result);
    }

    public function test_it_should_return_error_when_route_not_found(): void
    {
        $router = new Router;

        $result = $router->getMatchedRoute(
            new ServerRequest(HttpMethod::GET, new UriFactory()->createFromString('https://example.com/missing'))
        );

        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Route not found.', (string) $result);
    }

    public function test_it_should_not_register_a_duplicated_route_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "duplicated" already exists.');

        $router = new Router;

        $router->get(
            new UriPath('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));

        $router->post(
            new UriPath('/login'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
        )->setName(new Name('duplicated'));
    }

    public function test_it_should_register_a_route_inside_a_group(): void
    {
        $router = new Router;

        $router->group(function (RouteGroup $group): void {
            $group->get(
                new UriPath('/'),
                fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
            )->setName(new Name('home'));
        });

        $route = $router->routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
    }

    public function test_it_should_register_a_route_inside_a_group_with_middleware(): void
    {
        $router = new Router;

        $router->group(function (RouteGroup $group): void {
            $group->get(
                new UriPath('/'),
                fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
            )->setName(new Name('home'));
        })->addMiddleware(
            function (ServerRequest $request, RequestHandlerInterface $handler): Response {
                $response = $handler->handle($request);

                return $response->withBody(
                    new StreamFactory()->createFromString('Hello, World!')
                );
            }
        );

        $route = $router->routeRegistry->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/', (string) $route->path);
        $this->assertNotEmpty($route->middlewares);
    }

    public function test_it_should_register_a_route_inside_a_group_with_prefix(): void
    {
        $router = new Router;

        $router->group(function (RouteGroup $group): void {
            $group->get(
                new UriPath('/list'),
                fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
            )->setName(new Name('users.list'));
        })->addPrefix(new UriPath('/users'));

        $route = $router->routeRegistry->getRouteByName(new Name('users.list'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(HttpMethod::GET, $route->method);
        $this->assertEquals('/users/list', (string) $route->path);
    }
}
