<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteCollection;
use YSOCode\Berry\Domain\ValueObjects\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\RouteCollectionEvent;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteCollectionTest extends TestCase
{
    private Route $route;

    protected function setUp(): void
    {
        $this->route = new Route(
            HttpMethod::GET,
            new Path('/'),
            fn (ServerRequest $request): Response => new Response(HttpStatus::OK),
            new Name('home')
        );
    }

    public function test_it_should_register_a_route(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute($this->route);

        $route = $routeCollection->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('GET', $route->method->value);
        $this->assertEquals('/', (string) $route->path);
        $this->assertEquals('home', (string) $route->name);
    }

    public function test_it_should_return_a_route_when_path_exists(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute($this->route);

        $route = $routeCollection->getRouteByPath(new Path('/'));

        $this->assertInstanceOf(Route::class, $route);
    }

    public function test_it_should_return_a_route_when_name_exists(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute($this->route);

        $route = $routeCollection->getRouteByName(new Name('home'));

        $this->assertInstanceOf(Route::class, $route);
    }

    public function test_it_should_return_true_when_route_path_exists(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute($this->route);

        $this->assertTrue($routeCollection->hasRouteByPath(new Path('/')));
    }

    public function test_it_should_return_true_when_route_name_exists(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute($this->route);

        $this->assertTrue($routeCollection->hasRouteByName(new Name('home')));
    }

    public function test_it_should_emit_an_event_when_name_changes(): void
    {
        $called = false;
        $routeCollectionReceived = null;
        $dataReceived = null;

        $closure = function (RouteCollection $routeCollection, array $data) use (&$called, &$routeCollectionReceived, &$dataReceived): void {
            $called = true;
            $routeCollectionReceived = $routeCollection;
            $dataReceived = $data;
        };

        $routeCollection = new RouteCollection()
            ->on(RouteCollectionEvent::ROUTE_NAME_CHANGED, $closure);

        $routeCollection->addRoute(
            new Route(
                HttpMethod::GET,
                new Path('/'),
                fn (ServerRequest $request): Response => new Response(HttpStatus::OK)
            )
        );

        $route = $routeCollection->getRouteByPath(new Path('/'));
        if (! $route instanceof Route) {
            throw new RuntimeException('Route not found.');
        }

        $route->setName(new Name('home'));

        $name = $dataReceived['routeName'] ?? null;

        $this->assertTrue($called);
        $this->assertSame($routeCollection, $routeCollectionReceived);
        $this->assertInstanceOf(Name::class, $name);
        $this->assertEquals('home', (string) $name);
    }
}
