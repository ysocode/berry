<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteCollection;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\PathParameter;
use YSOCode\Berry\Domain\Types\PathParameterName;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RouteName;
use YSOCode\Berry\Domain\Types\RoutePathPattern;
use YSOCode\Berry\Domain\Types\UriPath;

final class RouteCollectionTest extends TestCase
{
    public function test_it_should_add_a_route(): void
    {
        $routeCollection = new RouteCollection;

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/users/{user}/profile'), new RequestHandler(HelloWorldHandler::class));
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));
        $getArticleRoute = new Route(HttpMethod::GET, new RoutePathPattern('/article/{slug}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($putProfileRoute);
        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($getArticleRoute);

        $reflection = new ReflectionObject($routeCollection);
        $routesBySegment = $reflection->getProperty('routesBySegment');
        $routesBySegmentValue = $routesBySegment->getValue($routeCollection);

        $expected = [
            '/' => [
                'children' => [
                    'users' => [
                        'children' => [
                            '{user}' => [
                                'children' => [
                                    'profile' => [
                                        'children' => [],
                                        'route' => $putProfileRoute,
                                    ],
                                ],
                                'route' => $getUserRoute,
                            ],
                        ],
                        'route' => null,
                    ],
                    'article' => [
                        'children' => [
                            '{slug}' => [
                                'children' => [],
                                'route' => $getArticleRoute,
                            ],
                        ],
                        'route' => null,
                    ],
                ],
                'route' => null,
            ],
        ];

        $this->assertSame($expected, $routesBySegmentValue);
    }

    public function test_it_should_return_resolved_route_when_route_exists(): void
    {
        $routeCollection = new RouteCollection;

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/users/{user}/profile'), new RequestHandler(HelloWorldHandler::class));
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));
        $getArticleRoute = new Route(HttpMethod::GET, new RoutePathPattern('/article/{slug}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($putProfileRoute);
        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($getArticleRoute);

        $actualPutProfileResolvedRoute = $routeCollection->getRouteByPath(new UriPath('/users/8847/profile'));
        $actualGetUserResolvedRoute = $routeCollection->getRouteByPath(new UriPath('/users/42'));
        $actualGetArticleResolvedRoute = $routeCollection->getRouteByPath(new UriPath('/article/example-slug'));

        $this->assertInstanceOf(ResolvedRoute::class, $actualPutProfileResolvedRoute);
        $this->assertInstanceOf(ResolvedRoute::class, $actualGetUserResolvedRoute);
        $this->assertInstanceOf(ResolvedRoute::class, $actualGetArticleResolvedRoute);
        $this->assertSame($putProfileRoute, $actualPutProfileResolvedRoute->route);
        $this->assertSame($getUserRoute, $actualGetUserResolvedRoute->route);
        $this->assertSame($getArticleRoute, $actualGetArticleResolvedRoute->route);
    }

    public function test_it_should_return_null_when_route_not_exists(): void
    {
        $routeCollection = new RouteCollection;

        $this->assertNull($routeCollection->getRouteByPath(new UriPath('/')));
    }

    public function test_it_should_reject_duplicated_path_pattern(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route conflict: /users/{user}');

        $routeCollection = new RouteCollection;

        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));
        $deleteUserRoute = new Route(HttpMethod::DELETE, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($deleteUserRoute);
    }

    public function test_it_should_return_resolved_route_when_path_exists(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute(
            new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class))
        );

        $resolvedRoute = $routeCollection->getRouteByPath(new UriPath('/users/8847'));

        $this->assertInstanceOf(ResolvedRoute::class, $resolvedRoute);
    }

    public function test_it_should_check_path_existence(): void
    {
        $routeCollection = new RouteCollection;
        $routeCollection->addRoute(
            new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class))
        );

        $this->assertTrue($routeCollection->hasRouteByPath(new UriPath('/users/42')));
        $this->assertFalse($routeCollection->hasRouteByPath(new UriPath('/home')));
    }

    public function test_it_should_reject_duplicated_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name "users.show" already exists.');

        $routeCollection = new RouteCollection;

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/users/{user}/profile'), new RequestHandler(HelloWorldHandler::class));
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($putProfileRoute);
        $routeCollection->addRoute($getUserRoute);

        $putProfileRoute->setName('users.show');
        $getUserRoute->setName('users.show');
    }

    public function test_it_should_return_route_when_name_exists(): void
    {
        $routeCollection = new RouteCollection;

        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($getUserRoute);

        $getUserRoute->setName('users.show');

        $route = $routeCollection->getRouteByName(new RouteName('users.show'));

        $this->assertInstanceOf(Route::class, $route);
    }

    public function test_it_should_check_name_existence(): void
    {
        $routeCollection = new RouteCollection;

        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($getUserRoute);

        $getUserRoute->setName('users.show');

        $this->assertTrue($routeCollection->hasRouteByName(new RouteName('users.show')));
        $this->assertFalse($routeCollection->hasRouteByName(new RouteName('home')));
    }

    public function test_it_should_contain_parameters_in_resolved_route(): void
    {
        $routeCollection = new RouteCollection;

        $route = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}/posts/{post}'), new RequestHandler(HelloWorldHandler::class));

        $routeCollection->addRoute($route);

        $resolvedRoute = $routeCollection->getRouteByPath(new UriPath('/users/42/posts/99'));

        $this->assertInstanceOf(ResolvedRoute::class, $resolvedRoute);

        $userParameter = $resolvedRoute->getParameter(new PathParameterName('user'));
        $postParameter = $resolvedRoute->getParameter(new PathParameterName('post'));

        $this->assertSame($route, $resolvedRoute->route);
        $this->assertInstanceOf(PathParameter::class, $userParameter);
        $this->assertEquals('user', (string) $userParameter->name);
        $this->assertEquals('42', $userParameter->value);
        $this->assertInstanceOf(PathParameter::class, $postParameter);
        $this->assertEquals('post', (string) $postParameter->name);
        $this->assertEquals('99', $postParameter->value);
    }
}
