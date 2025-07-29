<?php

declare(strict_types=1);

namespace Tests\Unit;

use DI\ContainerBuilder;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Tests\Fixtures\DummyController;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;

final class RouterTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        $this->container = $builder->build();
    }

    public function test_it_should_add_and_match_route(): void
    {
        $router = new Router;

        $handler = fn (Request $request): Response => new Response(Status::OK, 'ok');

        $path = new Path('/test');
        $router->get($path, $handler);

        $route = $router->getMatchedRoute(new Request(Method::GET, $path));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($handler, $route->handler);
    }

    public function test_it_should_throw_exception_when_adding_duplicate_route(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route GET /duplicate already exists.');

        $router = new Router;

        $path = new Path('/duplicate');
        $router->get($path, fn (Request $request): Response => new Response(Status::OK, 'first'));
        $router->get($path, fn (Request $request): Response => new Response(Status::OK, 'second'));
    }

    public function test_it_should_return_error_when_route_is_not_found(): void
    {
        $router = new Router;

        $result = $router->getMatchedRoute(new Request(Method::GET, new Path('/nope')));

        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Route not found.', (string) $result);
    }

    public function test_it_should_return_error_when_method_is_not_allowed(): void
    {
        $router = new Router;

        $path = new Path('/path');
        $router->get($path, fn (Request $request): Response => new Response(Status::OK, 'ok'));

        $result = $router->getMatchedRoute(new Request(Method::POST, $path));

        $this->assertInstanceOf(Error::class, $result);
        $this->assertEquals('Method not allowed.', (string) $result);
    }

    public function test_it_should_register_routes_for_all_http_methods(): void
    {
        $router = new Router;
        $path = new Path('/resource');

        $router->get($path, fn (Request $request): Response => new Response(Status::OK, 'get'));
        $router->post($path, fn (Request $request): Response => new Response(Status::OK, 'post'));
        $router->put($path, fn (Request $request): Response => new Response(Status::OK, 'put'));
        $router->delete($path, fn (Request $request): Response => new Response(Status::OK, 'delete'));
        $router->patch($path, fn (Request $request): Response => new Response(Status::OK, 'patch'));

        $methods = [
            Method::GET,
            Method::POST,
            Method::PUT,
            Method::DELETE,
            Method::PATCH,
        ];

        foreach ($methods as $method) {
            $route = $router->getMatchedRoute(new Request($method, $path));

            $this->assertInstanceOf(Route::class, $route);
            $this->assertEquals($method, $route->method);
        }
    }

    public function test_it_should_update_registered_paths_when_adding_route(): void
    {
        $router = new Router;
        $path = new Path('/registered');

        $router->get($path, fn (Request $request): Response => new Response(Status::OK, 'ok'));

        $reflection = new ReflectionClass($router);
        $property = $reflection->getProperty('registeredPaths');
        $registeredPaths = $property->getValue($router);

        $pathKey = (string) $path;

        $this->assertIsArray($registeredPaths);
        $this->assertArrayHasKey($pathKey, $registeredPaths);
        $this->assertTrue($registeredPaths[$pathKey]);
    }

    public function test_it_should_register_and_retrieve_named_route(): void
    {
        $router = new Router;

        $path = new Path('/named');
        $handler = fn (Request $request): Response => new Response(Status::OK, 'named');
        $name = new Name('namedRoute');

        $router->get(
            $path,
            $handler,
            $name
        );

        $route = $router->getRouteByName($name);

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals($path, $route->path);
        $this->assertEquals($name, $route->name);
        $this->assertEquals($handler, $route->handler);
        $this->assertEquals(Method::GET, $route->method);
    }

    public function test_it_should_return_null_when_named_route_is_not_found(): void
    {
        $router = new Router;

        $this->assertNull($router->getRouteByName(new Name('nonExistent')));
    }

    public function test_it_should_throw_exception_when_named_route_is_duplicated(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route name "duplicate" already exists.');

        $router = new Router;

        $router->get(
            new Path('/a'),
            fn (Request $request): Response => new Response(Status::OK, 'A'),
            new Name('duplicate')
        );
        $router->get(
            new Path('/b'),
            fn (Request $request): Response => new Response(Status::OK, 'B'),
            new Name('duplicate')
        );
    }

    public function test_it_should_accept_handler_as_value_object(): void
    {
        $router = new Router;

        $path = new Path('/controller');
        $handler = new Handler(DummyController::class, 'index');

        $router->get(
            $path,
            $handler
        );

        $route = $router->getMatchedRoute(new Request(Method::GET, $path));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertInstanceOf(Handler::class, $route->handler);

        $response = $route->handler->invoke(new Request(Method::GET, $path), $this->container);

        $this->assertEquals($path, $route->path);
        $this->assertEquals($handler, $route->handler);
        $this->assertSame('ok', $response->body);
    }
}
